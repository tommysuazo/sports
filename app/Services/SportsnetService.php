<?php

namespace App\Services;

use App\Enums\Games\NbaGameStatus;
use App\Models\NbaGame;
use App\Models\NbaGameData;
use App\Models\NbaPlayer;
use App\Models\NbaTeamScore;
use App\Models\NbaTeam;
use App\Repositories\NbaGameDataRepository;
use App\Repositories\NbaGameRepository;
use App\Repositories\NbaPlayerRepository;
use App\Repositories\NbaPlayerScoreRepository;
use App\Repositories\NbaTeamScoreRepository;
use App\Repositories\NbaTeamRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SportsnetService
{
    const BASE_URL = 'https://stats-api.sportsnet.ca';

    public function __construct(
        protected NbaGameRepository $nbaGameRepository,
        protected NbaTeamScoreRepository $nbaTeamScoreRepository,
        protected NbaPlayerScoreRepository $nbaPlayerScoreRepository,
        protected NbaGameDataRepository $nbaGameDataRepository,
        protected NbaTeamRepository $nbaTeamRepository,
        protected NbaPlayerRepository $nbaPlayerRepository,
    ) {}

    public function getGameDayUrl(string $league, Carbon $date)
    {
        return self::BASE_URL . "/ticker?league={$league}&day=" . $date->ToDateString();
    }

    public function getGameWeekUrl(string $league, int $week)
    {
        return self::BASE_URL . "/ticker?league={$league}&week={$week}&season_type=reg";
    }

    public function getGameUrl(string $league, string $gameId)
    {
        return self::BASE_URL . "/livetracker?league={$league}&id={$gameId}";
    }

    public function getTeamsUrl(string $league)
    {
        return self::BASE_URL . "/teams?league={$league}";
    }

    public function getTeamPlayersUrl(string $league, string $teamSportnetsId)
    {
        return self::BASE_URL . "/players?league={$league}&team_id={$teamSportnetsId}";
    }

    public function createNbaGame(string $sportsnetGameId): NbaGame
    {
        
        DB::beginTransaction();

        try {
            $nbaGameData = NbaGameData::firstWhere('external_id', $sportsnetGameId);

            if (!$nbaGameData) {
                $response = Http::get($this->getGameUrl('nba', $sportsnetGameId));

                if (!$response->successful()) {
                    throw new Exception("Failed to get nba game from sportsnet with id:" . $nbaGameData->id);
                }

                $nbaGameData = $this->nbaGameDataRepository->create($response->json('data.game'));
            }

            $gameData = $nbaGameData->data;

            $nbaGame = $this->nbaGameRepository->findByExternalId($sportsnetGameId);

            if ($nbaGame?->status === NbaGameStatus::FINAL->value) {
                return $nbaGame;
            }

            $awayTeam = $this->nbaTeamRepository->findByExternalId($gameData['visiting_team']['id']);
            $homeTeam = $this->nbaTeamRepository->findByExternalId($gameData['home_team']['id']);

            if (!$nbaGame) {
                $nbaGame = $this->nbaGameRepository->create(
                    [
                        'external_id' => $gameData['details']['id'],
                        'started_at' => Carbon::parse($gameData['details']['datetime'])->toDateTimeString(),
                        'status' => NbaGameStatus::getValueBySportsnetGameType($gameData['details']['status']),
                    ], $awayTeam, $homeTeam
                );
            }

            if ($gameData["details"]["status"] !== 'Final') {
                return $nbaGame;
            }

            $this->createNbaTeamScore($gameData, $nbaGame, $awayTeam);

            $this->createNbaTeamScore($gameData, $nbaGame, $homeTeam);

            $this->nbaGameRepository->updateStatus($nbaGame, NbaGameStatus::FINAL);
            
            DB::commit();
        
            return $nbaGame;
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function getNbaGamePlayerList(array $teamData): array
    {
        $starters = array_map(fn($starter) => $starter + ['is_starter' => true], $teamData['starters']);

        return array_merge($starters, $teamData['bench']);
    }

    public function createNbaTeamScore(array $gameData, NbaGame $nbaGame, NbaTeam $nbaTeam): NbaTeamScore
    {
        $teamKey = $nbaTeam->id === $nbaGame->home_team_id ? 'home_team' : 'visiting_team';

        $quarterKey = $teamKey . '_score';

        $quarters = $gameData['quarters'];

        $scoreData = $gameData[$teamKey]['boxscore_totals'];

        $this->createManyNbaPlayerScore($this->getNbaGamePlayerList($gameData[$teamKey]), $nbaGame, $nbaTeam);

        return $this->nbaTeamScoreRepository->create([
            'points' => $scoreData['points'],
            'first_half_points' => $quarters[0][$quarterKey] + $quarters[1][$quarterKey],
            'second_half_points' => $quarters[2][$quarterKey] + $quarters[3][$quarterKey],
            'first_quarter_points' => $quarters[0][$quarterKey],
            'second_quarter_points' => $quarters[1][$quarterKey],
            'third_quarter_points' => $quarters[2][$quarterKey],
            'fourth_quarter_points' => $quarters[3][$quarterKey],
            'assists' => $scoreData['assists'],
            'rebounds' => $scoreData['rebounds_total'],
            'steals' => $scoreData['steals'],
            'blocks' => $scoreData['blocked_shots'],
            'turnovers' => $scoreData['turnovers'],
            'fouls' => $scoreData['personal_fouls'],
            'field_goals_made' => $scoreData['field_goals_made'],
            'field_goals_attempted' => $scoreData['field_goals_attempted'],
            'three_pointers_made' => $scoreData['three_pointers_made'],
            'three_pointers_attempted' => $scoreData['three_pointers_attempted'],
            'free_throws_made' => $scoreData['free_throws_made'],
            'free_throws_attempted' => $scoreData['free_throws_attempted'],
        ], $nbaGame, $nbaTeam);
    }

    public function createManyNbaPlayerScore(array $playerScores, NbaGame $nbaGame, NbaTeam $nbaTeam): void
    {
        $playersSportnetIds = array_map(fn($player) => $player['id'], $playerScores);

        $currentPlayers = NbaPlayer::whereIn('external_id', $playersSportnetIds)->get();

        foreach ($playerScores as $playerScore) {
            $player = $currentPlayers->firstWhere('external_id', $playerScore['id']);

            if (is_null($player)) {
                $player = $this->nbaPlayerRepository->create([
                    'external_id' => $playerScore["id"],
                    'first_name' => $playerScore["first_name"],
                    'last_name' => $playerScore["last_name"],
                    'position' => $playerScore["short_position"],
                ], $nbaTeam);
            }

            if ($player->team_id != $nbaTeam->id) {
                $this->nbaPlayerRepository->updateTeam($player, $nbaTeam);
            }

            $this->nbaPlayerScoreRepository->create([
                'is_starter' => $playerScore['is_starter'] ?? false,
                'mins' => $playerScore['mins'],
                'points' => $playerScore['points'],
                'assists' => $playerScore['assists'],
                'rebounds' => $playerScore['rebounds_total'],
                'steals' => $playerScore['steals'],
                'blocks' => $playerScore['blocked_shots'],
                'turnovers' => $playerScore['turnovers'],
                'fouls' => $playerScore['personal_fouls'],
                'field_goals_made' => $playerScore['field_goals_made'],
                'field_goals_attempted' => $playerScore['field_goals_attempted'],
                'three_pointers_made' => $playerScore['three_pointers_made'],
                'three_pointers_attempted' => $playerScore['three_pointers_attempted'],
                'free_throws_made' => $playerScore['free_throws_made'],
                'free_throws_attempted' => $playerScore['free_throws_attempted'],
            ], $nbaGame, $nbaTeam, $player);
        }
    }
}
