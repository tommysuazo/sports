<?php

namespace App\Services;

use App\Enums\Games\NbaGameStatus;
use App\Models\NbaGame;
use App\Models\NbaPlayer;
use App\Models\NbaTeam;
use App\Models\NbaTeamStat;
use App\Repositories\NbaGameRepository;
use App\Repositories\NbaPlayerRepository;
use App\Repositories\NbaPlayerStatRepository;
use App\Repositories\NbaTeamRepository;
use App\Repositories\NbaTeamStatRepository;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NbaExternalOriginalService
{

    // public function createGame(string $gameId, array $awayQuarters, array $homeQuarters): null|NbaGame
    // {
    //     Log::info("Creating WNBA game with ID: {$gameId}");

    //     if ($this->nbaGameRepository->findByExternalId($gameId)) {
    //         return null;
    //     }

    //     $data = self::getGameByid($gameId);

    //     if (!$data['boxScoreTraditional']['homeTeam']['statistics']) {
    //         return null;
    //     }

    //     DB::beginTransaction();

    //     try {
    //         $startedAt = Carbon::parse($data['meta']['time']);

    //         $data = $data['boxScoreTraditional'];

    //         Log::info("away team: " . $data['awayTeamId'] . " - home team: " . $data['homeTeamId']);

    //         $awayTeam = NbaTeam::firstWhere('external_id', $data['awayTeamId']);
    //         $homeTeam = NbaTeam::firstWhere('external_id', $data['homeTeamId']);

    //         $game = $this->nbaGameRepository->create([
    //             'external_id' => $data['gameId'],
    //             'started_at' => $startedAt->toDateTimeString(),
    //         ], $awayTeam, $homeTeam);

    //         $this->createNbaTeamScore($data['awayTeam'] + ['quarters' => $awayQuarters], $game, $awayTeam);

    //         $this->createNbaTeamScore($data['homeTeam'] + ['quarters' => $homeQuarters], $game, $homeTeam);

    //         DB::commit();

    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         throw $e;
    //     }
        
    //     return $game;
    // }

    // public function createNbaTeamScore(array $data, NbaGame $nbaGame, NbaTeam $nbaTeam): NbaTeamScore
    // {
    //     $this->createManyNbaPlayerScore($data['players'], $nbaGame, $nbaTeam);

    //     $statistics = $data['statistics'];
    //     $quarters = collect($data['quarters']);

    //     $firstQuarterPoints = $quarters->firstWhere('period', 1)['score'];
    //     $secondQuarterPoints = $quarters->firstWhere('period', 2)['score'];
    //     $thirdQuarterPoints = $quarters->firstWhere('period', 3)['score'];
    //     $fourthQuarterPoints = $quarters->firstWhere('period', 4)['score'];
    //     $overtimes = $quarters->where('periodType', 'OVERTIME');

    //     return $this->nbaTeamScoreRepository->create([
    //         'points' => $statistics['points'],
    //         'first_half_points' => $firstQuarterPoints + $secondQuarterPoints,
    //         'second_half_points' => $thirdQuarterPoints + $fourthQuarterPoints + $overtimes->sum('scores'),
    //         'first_quarter_points' => $firstQuarterPoints,
    //         'second_quarter_points' => $secondQuarterPoints,
    //         'third_quarter_points' => $thirdQuarterPoints,
    //         'fourth_quarter_points' => $fourthQuarterPoints,
    //         'overtimes' => $overtimes->count(),
    //         'overtime_points' => $overtimes->sum('score'),
    //         'rebounds' => $statistics['reboundsTotal'],
    //         'assists' => $statistics['assists'],
    //         'steals' => $statistics['steals'],
    //         'blocks' => $statistics['blocks'],
    //         'turnovers' => $statistics['turnovers'],
    //         'fouls' => $statistics['foulsPersonal'],
    //         'field_goals_made' => $statistics['fieldGoalsMade'],
    //         'field_goals_attempted' => $statistics['fieldGoalsAttempted'],
    //         'three_pointers_made' => $statistics['threePointersMade'],
    //         'three_pointers_attempted' => $statistics['threePointersAttempted'],
    //         'free_throws_made' => $statistics['freeThrowsMade'],
    //         'free_throws_attempted' => $statistics['freeThrowsAttempted'],
    //     ], $nbaGame, $nbaTeam);
    // }

    // public function createManyNbaPlayerScore(array $data, NbaGame $nbaGame, NbaTeam $nbaTeam): void
    // {
    //     $playersExternalIds = array_map(fn($player) => $player['personId'], $data);

    //     $currentPlayers = NbaPlayer::whereIn('external_id', $playersExternalIds)->get();

    //     $starterCount = 0;

    //     foreach ($data as $playerScore) {
    //         $player = $currentPlayers->firstWhere('external_id', $playerScore['personId']);

    //         if (!$player) {
    //             $player = $this->nbaPlayerRepository->create([
    //                 'external_id' => $playerScore["personId"],
    //                 'first_name' => $playerScore["firstName"],
    //                 'last_name' => $playerScore["familyName"],
    //             ], $nbaTeam);
    //         }

    //         if ($player->team_id != $nbaTeam->id) {
    //             $this->nbaPlayerRepository->updateTeam($player, $nbaTeam);
    //         }

    //         $statistics = $playerScore['statistics'];

    //         if (!$statistics || empty($statistics['minutes']) || in_array($statistics['minutes'], ['0:00', '00:00'])) {
    //             Log::warning("No statistics found for player: {$player->external_id} in game: {$nbaGame->external_id}");
    //             continue;
    //         }

    //         $this->nbaPlayerScoreRepository->create([
    //             'is_starter' => $starterCount++ < 5,
    //             'mins' => $statistics['minutes'],
    //             'points' => $statistics['points'],
    //             'rebounds' => $statistics['reboundsTotal'],
    //             'assists' => $statistics['assists'],
    //             'steals' => $statistics['steals'],
    //             'blocks' => $statistics['blocks'],
    //             'turnovers' => $statistics['turnovers'],
    //             'fouls' => $statistics['foulsPersonal'],
    //             'field_goals_made' => $statistics['fieldGoalsMade'],
    //             'field_goals_attempted' => $statistics['fieldGoalsAttempted'],
    //             'three_pointers_made' => $statistics['threePointersMade'],
    //             'three_pointers_attempted' => $statistics['threePointersAttempted'],
    //             'free_throws_made' => $statistics['freeThrowsMade'],
    //             'free_throws_attempted' => $statistics['freeThrowsAttempted'],
    //         ], $nbaGame, $nbaTeam, $player);
    //     }
    // }
}
