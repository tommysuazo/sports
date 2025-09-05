<?php

namespace App\Services;

use App\Enums\Games\NbaGameStatus;
use App\Enums\Leagues\BasketballLeagueExternalEnum;
use App\Models\NbaGame;
use App\Models\NbaPlayer;
use App\Models\NbaTeam;
use App\Models\NbaTeamScore;
use App\Repositories\NbaGameRepository;
use App\Repositories\NbaPlayerRepository;
use App\Repositories\NbaPlayerScoreRepository;
use App\Repositories\NbaTeamRepository;
use App\Repositories\NbaTeamScoreRepository;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NbaExternalService
{
    // const HEADERS = [
    //     "ocp-apim-subscription-key" => "747fa6900c6c4e89a58b81b72f36eb96",
    //     "Accept" => " */*",
    //     "Accept-Encoding" => " gzip, deflate, br, zstd",
    //     "Accept-Language" => " es-ES,es;q=0.9",
    //     "Cache-Control" => " no-cache",
    //     "Connection" => " keep-alive",
    //     "Host" => " stats.nba.com",
    //     "Origin" => " https://www.nba.com",
    //     "Pragma" => " no-cache",
    //     "Referer" => " https://www.nba.com/",
    //     "Sec-Fetch-Dest" => " empty",
    //     "Sec-Fetch-Mode" => " cors",
    //     "Sec-Fetch-Site" => " same-site",
    //     "User-Agent" => " Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36",
    //     "sec-ch-ua"=> "\"Not(A:Brand\";v=\"99\", \"Google Chrome\";v=\"133\", \"Chromium\";v=\"133\"",
    //     "sec-ch-ua-mobile" => " ?0",
    //     "sec-ch-ua-platform" => "Windows",
    // ];
    
    // const BASE_URL = 'https://stats.nba.com';

    // protected string $league = 'nba';

    // public function __construct(
    //     protected NbaGameRepository $nbaGameRepository,
    //     protected NbaTeamRepository $nbaTeamRepository,
    //     protected NbaTeamScoreRepository $nbaTeamScoreRepository,
    //     protected NbaPlayerScoreService $nbaPlayerScoreService,
    //     protected NbaPlayerRepository $nbaPlayerRepository,
    //     protected NbaPlayerScoreRepository $nbaPlayerScoreRepository,
    // ) {
    // }

    // public function setLeague(string $league)
    // {
    //     $this->league = $league;
    // }

    // public function getExternalLeagueId()
    // {
    //     return match ($this->league) {
    //         'wnba' => '10',
    //         default => '00',
    //     };
    // }

    // public static function getAllPlayers()
    // {   
    //     return Http::withHeaders(self::HEADERS)
    //     ->get(self::BASE_URL . '/stats/playerindex?LeagueID=00&Season=2024-25');
    //         ->json('resultSets.0.rowSet');
    // }

    // public static function getWnbaPlayersData()
    // {   
    //     return Http::withHeaders(self::HEADERS)
    //         ->get(self::BASE_URL . '/stats/playerindex?LeagueID=10&Season=2025')
    //         ->json('resultSets.0.rowSet');
    // }

    // public function getGameDay(Carbon $date)
    // {
    //     $response = Http::withHeaders(self::HEADERS)
    //         ->get(
    //             self::BASE_URL . '/stats/scoreboardv3?DayOffset=0&LeagueID=' . $this->getExternalLeagueId() .
    //             '&GameDate=' . $date->toDateString()
    //         );

    //     if (!$response->successful()) {
    //         throw new Exception("Failed to get " . strtoupper($this->league) . " games from " . $date->toDateString(), 503);
    //     }
            
    //     return $response;
    // }

    // public function getGameByid(string $gameId)
    // {
    //     $response = Http::withHeaders(self::HEADERS)
    //         ->get(
    //             self::BASE_URL 
    //             . '/stats/boxscoretraditionalv3?' 
    //             . 'EndPeriod=1&EndRange=0&RangeType=0&StartPeriod=1&StartRange=0&GameID=' . $gameId
    //         );

    //     if (!$response->successful()) {
    //         throw new Exception("Failed to get " . strtoupper($this->league) . " game by id " . $gameId, 503);
    //     }
        
    //     return $response;
    // }

    // public static function getGameSummaryByid(string $gameId)
    // {
    //     $response = Http::withHeaders(self::HEADERS)
    //         ->get(self::BASE_URL . "/stats/boxscoresummaryv2?GameID=" . $gameId);

    //     if (!$response->successful()) {
    //         throw new Exception("Failed to get NBA game summary by id " . $gameId, 503);
    //     }
        
    //     return $response;
    // }

    // public static function getTodayLineups()
    // {
    //     $response = Http::withHeaders(self::HEADERS)
    //         ->get(self::BASE_URL . '/js/data/leaders/00_daily_lineups_' . now()->format('Ymd') . '.json');
        
    //     if (!$response->successful()) {
    //         throw new Exception("Failed to get NBA daily lineups", 503);
    //     }

    //     return $response;
    // }

    // public static function getTeamStats()
    // {
    //     $response = Http::withHeaders(self::HEADERS)
    //         ->get(
    //             self::BASE_URL . "/stats/leaguedashteamstats?Conference=&DateFrom=&DateTo=&Division=&GameScope=&GameSegment=&Height=&ISTRound=&LastNGames=0" .
    //             "&LeagueID=00&Location=&MeasureType=Base&Month=0&OpponentTeamID=0&Outcome=&PORound=0&PaceAdjust=N&PerMode=PerGame&Period=0&PlayerExperience=" .
    //             "&PlayerPosition=&PlusMinus=N&Rank=N&Season=2024-25&SeasonSegment=&SeasonType=Regular%20Season&ShotClockRange=&StarterBench=&TeamID=0&TwoWay=0" .
    //             "&VsConference=&VsDivision="
    //         );
        
    //     if (!$response->successful()) {
    //         throw new Exception("Failed to get NBA team stats ", 503);
    //     }

    //     return $response;
    // }
    
    // public static function getTeamOpponentStats()
    // {
    //     $response = Http::withHeaders(self::HEADERS)
    //         ->get(
    //             self::BASE_URL . "/stats/leaguedashteamstats?Conference=&DateFrom=&DateTo=&Division=&GameScope=&GameSegment=&Height=&ISTRound=&LastNGames=0" .
    //             "&LeagueID=00&Location=&MeasureType=Opponent&Month=0&OpponentTeamID=0&Outcome=&PORound=0&PaceAdjust=N&PerMode=PerGame&Period=0&PlayerExperience=" .
    //             "&PlayerPosition=&PlusMinus=N&Rank=N&Season=2024-25&SeasonSegment=&SeasonType=Regular%20Season&ShotClockRange=&StarterBench=&TeamID=0" .
    //             "&TwoWay=0&VsConference=&VsDivision="
    //         );
        
    //     if (!$response->successful()) {
    //         throw new Exception("Failed to get NBA team opponent stats ", 503);
    //     }

    //     return $response;
    // }

    // public function importGamesByDate(Carbon $date)
    // {   
    //     Log::info("Importing " . strtoupper($this->league) . " games for date: " . $date->toDateString());

    //     $games = $this->getGameDay($date)->json('scoreboard.games');

    //     foreach ($games as $game) {
    //         $this->createGame($game['gameId'], $game['awayTeam']['periods'], $game['homeTeam']['periods']);
    //     }
    // }

    // public function createGame(string $gameId, array $awayQuarters, array $homeQuarters): null|NbaGame
    // {
    //     Log::info("Creating " . strtoupper($this->league) . " game with ID: " . $gameId);

    //     if ($this->nbaGameRepository->findByExternalId($gameId)) {
    //         return null;
    //     }

    //     $data = $this->getGameByid($gameId);

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