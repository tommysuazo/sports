<?php

namespace App\Services;

use App\Exceptions\KnownException;
use App\Models\WnbaGame;
use App\Models\WnbaPlayer;
use App\Models\WnbaTeam;
use App\Models\WnbaTeamScore;
use App\Repositories\WnbaGameRepository;
use App\Repositories\WnbaPlayerRepository;
use App\Repositories\WnbaPlayerScoreRepository;
use App\Repositories\WnbaTeamRepository;
use App\Repositories\WnbaTeamScoreRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WnbaStatsService
{
    const HEADERS = [
        "ocp-apim-subscription-key" => "747fa6900c6c4e89a58b81b72f36eb96",
        "Accept" => " */*",
        "Accept-Encoding" => " gzip, deflate, br, zstd",
        "Accept-Language" => " es-ES,es;q=0.9",
        "Cache-Control" => " no-cache",
        "Connection" => " keep-alive",
        "Host" => " stats.nba.com",
        "Origin" => " https://www.nba.com",
        "Pragma" => " no-cache",
        "Referer" => " https://www.nba.com/",
        "Sec-Fetch-Dest" => " empty",
        "Sec-Fetch-Mode" => " cors",
        "Sec-Fetch-Site" => " same-site",
        "User-Agent" => " Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36",
        "sec-ch-ua"=> "\"Not(A:Brand\";v=\"99\", \"Google Chrome\";v=\"133\", \"Chromium\";v=\"133\"",
        "sec-ch-ua-mobile" => " ?0",
        "sec-ch-ua-platform" => "Windows",
    ];
    
    const BASE_URL = 'https://stats.nba.com';

    public function __construct(
        protected WnbaGameRepository $nbaGameRepository,
        protected WnbaTeamRepository $nbaTeamRepository,
        protected WnbaTeamScoreRepository $nbaTeamScoreRepository,
        protected WnbaPlayerScoreService $nbaPlayerScoreService,
        protected WnbaPlayerRepository $nbaPlayerRepository,
        protected WnbaPlayerScoreRepository $nbaPlayerScoreRepository,
    ) {
    }

    public static function getPlayers()
    {   
        $request = Http::withHeaders(self::HEADERS)
            ->get(self::BASE_URL . '/stats/playerindex?LeagueID=10&Season=2024-25');

            if (!$request->successful()) {
                throw new KnownException("Fallo en el retorno de jugadores de Wnba con la clase " . __CLASS__);
            }

        $players = $request->json('resultSets.0.rowSet');

        return array_map(
            fn($player) => [
                'external_id' => $player[0],
                'first_name' => $player[2],
                'last_name' => $player[1],
                'team_external_id' => $player[4]
            ],
        $players);
    }

    public static function getGamesByDate(Carbon $date)
    {
        $request = Http::withHeaders(self::HEADERS)
            ->get(self::BASE_URL . '/stats/scoreboardv3?DayOffset=0&LeagueID=10&GameDate=' . $date->toDateString());

        if (!$request->successful()) {
            throw new KnownException("Fallo al intentar conseguir los juegos de WNBA del dia " . $date->toDateString());
        }
            
        return $request->json('scoreboard.games');
    }

    public function getGameByid(string $gameId)
    {
        $request = Http::withHeaders(self::HEADERS)
            ->get(self::BASE_URL . '/stats/boxscoretraditionalv3' .
                '?EndPeriod=1&EndRange=0&RangeType=0&StartPeriod=1&StartRange=0&GameID=' . $gameId
            );

        if (!$request->successful()) {
            throw new knownException("Fallo al intentar obtener el juego con ID {$gameId}");
        }
        
        return $request;
    }

    // public static function getTodayLineups()
    // {
    //     $request = Http::withHeaders(self::HEADERS)
    //         ->get(self::BASE_URL . '/js/data/leaders/00_daily_lineups_' . now()->format('Ymd') . '.json');
        
    //     if (!$request->successful()) {
    //         throw new knownException("Fallo al intentar obtener las alineaciones del dia de hoy");
    //     }

    //     return $request;
    // }

    public function importGamesByDate(Carbon $date)
    {   
        Log::info("Importando juegos de WNBA de la fecha " . $date->toDateString());

        $games = $this->getGamesByDate($date);

        foreach ($games as $game) {
            $this->createGame($game['gameId'], $game['awayTeam']['periods'], $game['homeTeam']['periods']);
        }
    }

    public function createGame(string $gameId, array $awayQuarters, array $homeQuarters): null|WnbaGame
    {
        Log::info("Creando juego de WNBA con ID externo " . $gameId);

        if ($this->nbaGameRepository->findByExternalId($gameId)) {
            return null;
        }

        $data = $this->getGameByid($gameId);

        if (!$data['boxScoreTraditional']['homeTeam']['statistics']) {
            return null;
        }

        DB::beginTransaction();

        try {
            $startedAt = Carbon::parse($data['meta']['time']);

            $data = $data['boxScoreTraditional'];

            Log::info("away team: " . $data['awayTeamId'] . " - home team: " . $data['homeTeamId']);

            $awayTeam = WnbaTeam::firstWhere('external_id', $data['awayTeamId']);
            $homeTeam = WnbaTeam::firstWhere('external_id', $data['homeTeamId']);

            $game = $this->nbaGameRepository->create([
                'external_id' => $data['gameId'],
                'started_at' => $startedAt->toDateTimeString(),
            ], $awayTeam, $homeTeam);

            $this->createWnbaTeamScore($data['awayTeam'] + ['quarters' => $awayQuarters], $game, $awayTeam);

            $this->createWnbaTeamScore($data['homeTeam'] + ['quarters' => $homeQuarters], $game, $homeTeam);

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
        
        return $game;
    }

    public function createWnbaTeamScore(array $data, WnbaGame $nbaGame, WnbaTeam $nbaTeam): WnbaTeamScore
    {
        $this->createManyWnbaPlayerScore($data['players'], $nbaGame, $nbaTeam);

        $statistics = $data['statistics'];
        $quarters = collect($data['quarters']);

        $firstQuarterPoints = $quarters->firstWhere('period', 1)['score'];
        $secondQuarterPoints = $quarters->firstWhere('period', 2)['score'];
        $thirdQuarterPoints = $quarters->firstWhere('period', 3)['score'];
        $fourthQuarterPoints = $quarters->firstWhere('period', 4)['score'];
        $overtimes = $quarters->where('periodType', 'OVERTIME');

        return $this->nbaTeamScoreRepository->create([
            'points' => $statistics['points'],
            'first_half_points' => $firstQuarterPoints + $secondQuarterPoints,
            'second_half_points' => $thirdQuarterPoints + $fourthQuarterPoints + $overtimes->sum('scores'),
            'first_quarter_points' => $firstQuarterPoints,
            'second_quarter_points' => $secondQuarterPoints,
            'third_quarter_points' => $thirdQuarterPoints,
            'fourth_quarter_points' => $fourthQuarterPoints,
            'overtimes' => $overtimes->count(),
            'overtime_points' => $overtimes->sum('score'),
            'rebounds' => $statistics['reboundsTotal'],
            'assists' => $statistics['assists'],
            'steals' => $statistics['steals'],
            'blocks' => $statistics['blocks'],
            'turnovers' => $statistics['turnovers'],
            'fouls' => $statistics['foulsPersonal'],
            'field_goals_made' => $statistics['fieldGoalsMade'],
            'field_goals_attempted' => $statistics['fieldGoalsAttempted'],
            'three_pointers_made' => $statistics['threePointersMade'],
            'three_pointers_attempted' => $statistics['threePointersAttempted'],
            'free_throws_made' => $statistics['freeThrowsMade'],
            'free_throws_attempted' => $statistics['freeThrowsAttempted'],
        ], $nbaGame, $nbaTeam);
    }

    public function createManyWnbaPlayerScore(array $data, WnbaGame $nbaGame, WnbaTeam $nbaTeam): void
    {
        $playersExternalIds = array_map(fn($player) => $player['personId'], $data);

        $currentPlayers = WnbaPlayer::whereIn('external_id', $playersExternalIds)->get();

        $starterCount = 0;

        foreach ($data as $playerScore) {
            $player = $currentPlayers->firstWhere('external_id', $playerScore['personId']);

            if (!$player) {
                $player = $this->nbaPlayerRepository->create([
                    'external_id' => $playerScore["personId"],
                    'first_name' => $playerScore["firstName"],
                    'last_name' => $playerScore["familyName"],
                ], $nbaTeam);
            }

            if ($player->team_id != $nbaTeam->id) {
                $this->nbaPlayerRepository->updateTeam($player, $nbaTeam);
            }

            $statistics = $playerScore['statistics'];

            if (!$statistics || empty($statistics['minutes']) || in_array($statistics['minutes'], ['0:00', '00:00'])) {
                Log::warning("No statistics found for player: {$player->external_id} in game: {$nbaGame->external_id}");
                continue;
            }

            $this->nbaPlayerScoreRepository->create([
                'is_starter' => $starterCount++ < 5,
                'mins' => $statistics['minutes'],
                'points' => $statistics['points'],
                'rebounds' => $statistics['reboundsTotal'],
                'assists' => $statistics['assists'],
                'steals' => $statistics['steals'],
                'blocks' => $statistics['blocks'],
                'turnovers' => $statistics['turnovers'],
                'fouls' => $statistics['foulsPersonal'],
                'field_goals_made' => $statistics['fieldGoalsMade'],
                'field_goals_attempted' => $statistics['fieldGoalsAttempted'],
                'three_pointers_made' => $statistics['threePointersMade'],
                'three_pointers_attempted' => $statistics['threePointersAttempted'],
                'free_throws_made' => $statistics['freeThrowsMade'],
                'free_throws_attempted' => $statistics['freeThrowsAttempted'],
            ], $nbaGame, $nbaTeam, $player);
        }
    }
}