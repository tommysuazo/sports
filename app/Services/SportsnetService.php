<?php

namespace App\Services;

use App\Models\NbaGame;
use App\Models\NbaGameData;
use App\Models\NbaPlayer;
use App\Models\NbaScore;
use App\Models\NbaTeam;
use App\Repositories\NbaGameDataRepository;
use App\Repositories\NbaGameRepository;
use App\Repositories\NbaPlayerScoreRepository;
use App\Repositories\NbaScoreRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;

class SportsnetService
{
    const NBA_GAMEDAY_BASE_URL = "https://mobile-statsv2.sportsnet.ca/ticker?league=nba&day=";
    const NBA_GAME_BASE_URL = "https://stats-api.sportsnet.ca/livetracker?league=nba&id=";

    public function __construct(
        protected NbaGameRepository $nbaGameRepository,
        protected NbaScoreRepository $nbaScoreRepository,
        protected NbaPlayerScoreRepository $nbaPlayerScoreRepository,
        protected NbaGameDataRepository $nbaGameDataRepository,
    ){
    }

    public function createManyNbaGamesByDate(Carbon $date)
    {
        // $gameDayResponse = Http::get(self::NBA_GAMEDAY_BASE_URL . $date->ToDateString());

        // if (!$gameDayResponse->successful()) {
        //     throw new Exception("Failed to get NBA games from " . $date->toDateString(), $gameDayResponse->status());
        // }
        
        // foreach ($gameDayResponse->json('data.games') as $game) {
        //     $this->createNbaGame($game['id']);
        // }

        $this->createNbaGame();

    }

    public function createNbaGame(string $sportsnetGameId = '10dee9b6-c101-4bba-82af-f43f3dcfff30'): NbaGame
    {
        $gameData = NbaGameData::firstWhere('sportsnet_id', $sportsnetGameId);
        
        if (!$gameData) {
            $response = Http::get(self::NBA_GAME_BASE_URL . $sportsnetGameId);

            if (!$response->successful()) {
                throw new Exception("Failed to get nba game from sportsnet with id:" . $gameData->id);
            }

            $gameData = $this->nbaGameDataRepository->create($response->json('data.game'));
        }

        $game = NbaGame::firstWhere('sportsnet_id', $sportsnetGameId);

        if ($game) {
            return $game;
        }

        $awayScore = $this->createNbaScore($gameData, false);

        $awayPlayers = array_merge($gameData['visiting_team']['starters'], $gameData['visiting_team']['bench']);

        $this->createManyNbaPlayerScore($awayPlayers, $awayScore);
        
        // $awayInjuries = $away['injuries'];

        $homeScore = $this->createNbaScore($gameData);

        $homePlayers = array_merge($gameData['home_team']['starters'], $gameData['home_team']['bench']);

        $this->createManyNbaPlayerScore($homePlayers, $homeScore);

        // $homeInjuries = $home['injuries'];

        return $this->nbaGameRepository->create([
            'sportsnet_id' => $gameData['details']['id'],
            'started_at' => Carbon::parse($gameData['details']['datetime'])->toDateTimeString(),
        ], $awayScore, $homeScore);
    }

    public function createNbaScore(array $gameData, bool $isHomeTeam = true): NbaScore
    {
        $teamKey = $isHomeTeam ? 'home_team' : 'visiting_team';

        $quarterKey = $teamKey . '_score';

        $quarters = $gameData['quarters'];

        $scoreData = $gameData[$teamKey]['boxscore_totals'];

        $team = NbaTeam::firstWhere('code', $gameData[$teamKey]['short_name']);

        return $this->nbaScoreRepository->create([
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
        ], $team);
    }

    public function createManyNbaPlayerScore(array $playersScores, NbaScore $nbaScore): void
    {
        $playersSportnetIds = array_map(fn($player) => $player['id'], $playersScores);
        
        $currentPlayers = NbaPlayer::where('sportsnet_id', $playersSportnetIds)->get();

        $team = $nbaScore->team;

        foreach ($playersScores as $playerScore) {
            $player = $currentPlayers->firstWhere('sportsnet_id', $playerScore['id']);

            if (is_null($player)) {
                $player = NbaPlayer::where('first_name', $playerScore["first_name"])
                    ->where('last_name',  $playerScore["last_name"])
                    ->first();

                if (is_null($player)) {
                    $player = NbaPlayer::create([
                        'sportsnet_id' => $playerScore["id"],
                        'first_name' => $playerScore["first_name"],
                        'last_name' => $playerScore["last_name"],
                        'team_id' => $team->id,
                    ]);
                }
            }

            $this->nbaPlayerScoreRepository->create([
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
            ], $player, $nbaScore);
        }

    }
}