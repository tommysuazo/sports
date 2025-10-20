<?php

namespace App\Services;

use App\Enums\NBA\NbaExternalGameStatusEnum;
use App\Exceptions\KnownException;
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
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NbaStatsService
{
    const BASE_URL = 'https://stats.nba.com';

    public function __construct(
        protected NbaGameRepository $nbaGameRepository,
        protected NbaTeamRepository $nbaTeamRepository,
        protected NbaTeamScoreRepository $nbaTeamScoreRepository,
        protected NbaPlayerScoreService $nbaPlayerScoreService,
        protected NbaPlayerRepository $nbaPlayerRepository,
        protected NbaPlayerScoreRepository $nbaPlayerScoreRepository,
    ) {
    }

    public static function getPlayers()
    {   
        $request = Http::withHeaders(self::headers())
            ->get(self::BASE_URL . '/stats/playerindex?LeagueID=00&Season=2024-25');

        if (!$request->successful()) {
            throw new KnownException("Fallo en el retorno de jugadores de Nba con la clase " . __CLASS__);
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
        $request = Http::withHeaders(self::headers())
            ->get(self::BASE_URL . '/stats/scoreboardv3?DayOffset=0&LeagueID=00&GameDate=' . $date->toDateString());

        if (!$request->successful()) {
            throw new KnownException("Fallo al intentar conseguir los juegos de NBA del dia " . $date->toDateString());
        }
            
        return $request->json('scoreboard.games');
    }

    public function getGameByid(string $gameId)
    {
        $request = Http::withHeaders(self::headers())->get(self::BASE_URL . '/stats/boxscoretraditionalv3?GameID=' . $gameId);

        if (!$request->successful()) {
            throw new KnownException("Fallo al intentar obtener el juego con ID {$gameId}");
        }
        
        return $request;
    }

    public static function getTodayLineups()
    {
        $request = Http::withHeaders(self::headers())
            ->get(self::BASE_URL . '/js/data/leaders/00_daily_lineups_' . now()->format('Ymd') . '.json');
        
        if (!$request->successful()) {
            throw new KnownException("Fallo al intentar obtener las alineaciones del dia de hoy");
        }

        return $request->json();
    }

    public function importGamesByDate(Carbon $date)
    {   
        Log::info("Importando juegos de NBA de la fecha " . $date->toDateString());

        $games = $this->getGamesByDate($date);

        foreach ($games as $gameData) {
            $this->createGame($gameData);
        }
    }

    public function createGame(array $gameData): NbaGame
    {
        Log::info("Creando juego de NBA con ID externo " . $gameData['gameId']);

        DB::beginTransaction();

        
        try {
            $game = NbaGame::firstWhere('external_id', $gameData['gameId']);
            $awayTeam = NbaTeam::firstWhere('external_id', $gameData['awayTeam']['teamId']);
            $homeTeam = NbaTeam::firstWhere('external_id', $gameData['homeTeam']['teamId']);

            if (!$awayTeam || !$homeTeam) {
                throw new KnownException("No se pudieron localizar los equipos del juego {$gameData['gameId']}");
            }

            if (!$game) {
                $game = NbaGame::create([
                    'external_id' => $gameData['gameId'],
                    'away_team_id' => $awayTeam->id,
                    'home_team_id' => $homeTeam->id,
                    'start_at' => $gameData['gameTimeUTC'],
                    'is_completed' => false,
                ]);
            }

            if (!$game->is_completed && $gameData['gameStatus'] === NbaExternalGameStatusEnum::COMPLETED->value) {
                Log::info("Away team: {$gameData['awayTeam']['teamTricode']} - Home team: {$gameData['homeTeam']['teamTricode']}");
    
                $data = $this->getGameByid($gameData['gameId']);
    
                $data = $data['boxScoreTraditional'];
    
                $awayTeamScore = $this->createNbaTeamScore(
                    $data['awayTeam'] + ['quarters' => $gameData['awayTeam']['periods']], 
                    $game, 
                    $game->awayTeam
                );
    
                $homeTeamScore = $this->createNbaTeamScore(
                    $data['homeTeam'] + ['quarters' => $gameData['homeTeam']['periods']],
                    $game,
                    $game->homeTeam
                );
    
                $game->is_completed = true;
    
                $awayTeamWon = $awayTeamScore->points > $homeTeamScore->points;
                $game->winner_team_id = $awayTeamWon ? $game->away_team_id : $game->home_team_id;
                $game->save();
    
                $winnerTeam = $awayTeamWon ? $awayTeam : $homeTeam;
                $loserTeam = $awayTeamWon ? $homeTeam : $awayTeam;

                $this->updateTeamRecords($winnerTeam, $loserTeam);
            }

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
        
        return $game;
    }

    public function createNbaTeamScore(array $data, NbaGame $nbaGame, NbaTeam $nbaTeam): NbaTeamScore
    {
        $this->createManyNbaPlayerScore($data['players'], $nbaGame, $nbaTeam);

        $statistics = $data['statistics'];
        $quarters = collect($data['quarters']);

        $firstQuarterPoints = data_get($quarters->firstWhere('period', 1), 'score', 0);
        $secondQuarterPoints = data_get($quarters->firstWhere('period', 2), 'score', 0);
        $thirdQuarterPoints = data_get($quarters->firstWhere('period', 3), 'score', 0);
        $fourthQuarterPoints = data_get($quarters->firstWhere('period', 4), 'score', 0);
        $overtimes = $quarters->where('periodType', 'OVERTIME');

        return $this->nbaTeamScoreRepository->create([
            'points' => $statistics['points'],
            'is_away' => $nbaGame->away_team_id === $nbaTeam->id,
            'first_half_points' => $firstQuarterPoints + $secondQuarterPoints,
            'second_half_points' => $thirdQuarterPoints + $fourthQuarterPoints + $overtimes->sum('score'),
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

    public function createManyNbaPlayerScore(array $data, NbaGame $nbaGame, NbaTeam $nbaTeam): void
    {
        $playersExternalIds = array_map(fn($player) => $player['personId'], $data);

        $currentPlayers = NbaPlayer::whereIn('external_id', $playersExternalIds)->get();

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

            $statistics = $playerScore['statistics'];

            if (!$statistics || empty($statistics['minutes']) || in_array($statistics['minutes'], ['0:00', '00:00'])) {
                Log::warning("No statistics found for player: {$player->external_id} in game: {$nbaGame->external_id}");
                continue;
            }

            $this->nbaPlayerScoreRepository->create([
                'is_starter' => $starterCount++ < 5,
                'is_away' => $nbaGame->away_team_id === $nbaTeam->id,
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

    protected function updateTeamRecords(NbaTeam $winner, NbaTeam $loser): void
    {
        $this->nbaTeamRepository->syncRecordWithGames($winner);
        $this->nbaTeamRepository->syncRecordWithGames($loser);
    }

    protected static function headers(): array
    {
        return [
            "Accept-Encoding" => " gzip, deflate, br, zstd",
            "Cache-Control" => " no-cache",
            "Connection" => " keep-alive",
            "Origin" => " https://www.nba.com",
            "Pragma" => " no-cache",
            "Referer" => " https://www.nba.com/",
            "Sec-Fetch-Mode" => " cors",
            "User-Agent" => " Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36",
            "sec-ch-ua"=> "\"Not(A:Brand\";v=\"99\", \"Google Chrome\";v=\"133\", \"Chromium\";v=\"133\"",
            "sec-ch-ua-mobile" => " ?0",
            "sec-ch-ua-platform" => "Windows",
        ];
    }
}
