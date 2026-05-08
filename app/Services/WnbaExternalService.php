<?php

namespace App\Services;

use App\Enums\WNBA\WnbaExternalGameStatusEnum;
use App\Exceptions\KnownException;
use App\Models\WnbaGame;
use App\Models\WnbaPlayer;
use App\Models\WnbaTeam;
use App\Models\WnbaTeamStat;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WnbaExternalService
{
    const BASE_URL = 'https://stats.nba.com';

    public static function getPlayers()
    {
        $season = config('wnba.season', '2026');

        $request = Http::withHeaders(self::headers())
            ->get(self::BASE_URL . "/stats/playerindex?LeagueID=10&Season={$season}");

        if (!$request->successful()) {
            throw new KnownException("Fallo en el retorno de jugadores de WNBA con la clase " . __CLASS__);
        }

        $players = $request->json('resultSets.0.rowSet');

        return array_map(
            fn ($player) => [
                'external_id' => $player[0],
                'first_name' => $player[2],
                'last_name' => $player[1],
                'team_external_id' => $player[4],
            ],
            $players
        );
    }

    public static function getGamesByDate(Carbon $date)
    {
        $request = Http::withHeaders(self::headers())
            ->get(self::BASE_URL . '/stats/scoreboardv3?DayOffset=0&LeagueID=10&GameDate=' . $date->toDateString());

        if (!$request->successful()) {
            throw new KnownException("Fallo al intentar conseguir los juegos de WNBA del dia " . $date->toDateString());
        }

        return $request->json('scoreboard.games');
    }

    public function getGameByid(string $gameId)
    {
        $request = Http::withHeaders(self::headers())
            ->get(self::BASE_URL . '/stats/boxscoretraditionalv3' .
                '?EndPeriod=1&EndRange=0&RangeType=0&StartPeriod=1&StartRange=0&GameID=' . $gameId
            );

        if (!$request->successful()) {
            throw new KnownException("Fallo al intentar obtener el juego WNBA con ID {$gameId}");
        }

        return $request;
    }

    public function importGamesByDate(Carbon $date): ?WnbaGame
    {
        Log::info("Importando juegos de WNBA de la fecha " . $date->toDateString());

        $games = $this->getGamesByDate($date);

        $lastGameImported = null;

        foreach ($games as $gameData) {
            logger()->info("Importando juego WNBA con ID externo " . $gameData['gameId']);
            $lastGameImported = $this->createGame($gameData);
        }

        return $lastGameImported;
    }

    public function createGame(array $gameData): WnbaGame
    {
        Log::info("Creando juego de WNBA con ID externo " . $gameData['gameId']);

        DB::beginTransaction();

        try {
            $game = WnbaGame::firstWhere('external_id', $gameData['gameId']);
            $awayTeam = WnbaTeam::firstWhere('external_id', $gameData['awayTeam']['teamId']);
            $homeTeam = WnbaTeam::firstWhere('external_id', $gameData['homeTeam']['teamId']);

            if (!$awayTeam || !$homeTeam) {
                throw new KnownException("No se pudieron localizar los equipos WNBA del juego {$gameData['gameId']}");
            }

            if (!$game) {
                $game = WnbaGame::create([
                    'external_id' => $gameData['gameId'],
                    'away_team_id' => $awayTeam->id,
                    'home_team_id' => $homeTeam->id,
                    'start_at' => $gameData['gameTimeUTC'],
                    'is_completed' => false,
                ]);
            }

            if (!$game->is_completed && $gameData['gameStatus'] === WnbaExternalGameStatusEnum::COMPLETED->value) {
                Log::info("Away team: {$gameData['awayTeam']['teamTricode']} - Home team: {$gameData['homeTeam']['teamTricode']}");

                $data = $this->getGameByid($gameData['gameId']);
                $data = $data['boxScoreTraditional'];

                $awayTeamStat = $this->createWnbaTeamStat(
                    $data['awayTeam'] + ['quarters' => $gameData['awayTeam']['periods']],
                    $game,
                    $game->awayTeam
                );

                $homeTeamStat = $this->createWnbaTeamStat(
                    $data['homeTeam'] + ['quarters' => $gameData['homeTeam']['periods']],
                    $game,
                    $game->homeTeam
                );

                $game->is_completed = true;

                $awayTeamWon = $awayTeamStat->points > $homeTeamStat->points;
                $game->winner_team_id = $awayTeamWon ? $game->away_team_id : $game->home_team_id;
                $game->save();

                $this->updateTeamRecords($awayTeamWon ? $awayTeam : $homeTeam, $awayTeamWon ? $homeTeam : $awayTeam);
            } elseif ($game->start_at !== $gameData['gameTimeUTC']) {
                $game->start_at = $gameData['gameTimeUTC'];
                $game->save();
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $game;
    }

    public function createWnbaTeamStat(array $data, WnbaGame $wnbaGame, WnbaTeam $wnbaTeam): WnbaTeamStat
    {
        $this->createManyWnbaPlayerStat($data['players'], $wnbaGame, $wnbaTeam);

        $statistics = $data['statistics'];
        $quarters = collect($data['quarters']);

        $firstQuarterPoints = data_get($quarters->firstWhere('period', 1), 'score', 0);
        $secondQuarterPoints = data_get($quarters->firstWhere('period', 2), 'score', 0);
        $thirdQuarterPoints = data_get($quarters->firstWhere('period', 3), 'score', 0);
        $fourthQuarterPoints = data_get($quarters->firstWhere('period', 4), 'score', 0);
        $overtimes = $quarters->where('periodType', 'OVERTIME');

        return WnbaTeamStat::create([
            'game_id' => $wnbaGame->id,
            'team_id' => $wnbaTeam->id,
            'is_away' => $wnbaGame->away_team_id === $wnbaTeam->id,
            'points' => $statistics['points'],
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
        ]);
    }

    public function createManyWnbaPlayerStat(array $data, WnbaGame $wnbaGame, WnbaTeam $wnbaTeam): void
    {
        $playersExternalIds = array_map(fn ($player) => $player['personId'], $data);
        $currentPlayers = WnbaPlayer::whereIn('external_id', $playersExternalIds)->get();

        $starterCount = 0;

        foreach ($data as $playerStat) {
            $player = $currentPlayers->firstWhere('external_id', $playerStat['personId']);

            if (!$player) {
                $player = WnbaPlayer::create([
                    'external_id' => $playerStat['personId'],
                    'first_name' => $playerStat['firstName'],
                    'last_name' => $playerStat['familyName'],
                    'team_id' => $wnbaTeam->id,
                ]);
            }

            if ($player->team_id != $wnbaTeam->id) {
                $player->update(['team_id' => $wnbaTeam->id]);
            }

            $statistics = $playerStat['statistics'];

            if (!$statistics || empty($statistics['minutes']) || in_array($statistics['minutes'], ['0:00', '00:00'])) {
                Log::warning("No statistics found for WNBA player: {$player->external_id} in game: {$wnbaGame->external_id}");
                continue;
            }

            $wnbaGame->playerStats()->create([
                'team_id' => $wnbaTeam->id,
                'player_id' => $player->id,
                'is_away' => $wnbaGame->away_team_id === $wnbaTeam->id,
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
            ]);
        }
    }

    protected function updateTeamRecords(WnbaTeam $winner, WnbaTeam $loser): void
    {
        $this->syncRecordWithGames($winner);
        $this->syncRecordWithGames($loser);
    }

    protected function syncRecordWithGames(WnbaTeam $team): void
    {
        $record = WnbaGame::query()
            ->join('wnba_team_stats as team_stat', function ($join) use ($team) {
                $join->on('team_stat.game_id', '=', 'wnba_games.id')
                    ->where('team_stat.team_id', '=', $team->id);
            })
            ->join('wnba_team_stats as opponent_stat', function ($join) {
                $join->on('opponent_stat.game_id', '=', 'wnba_games.id')
                    ->whereColumn('opponent_stat.team_id', '!=', 'team_stat.team_id');
            })
            ->where('wnba_games.is_completed', true)
            ->where(function ($query) use ($team) {
                $query->where('wnba_games.home_team_id', $team->id)
                    ->orWhere('wnba_games.away_team_id', $team->id);
            })
            ->selectRaw('
                SUM(CASE WHEN team_stat.points > opponent_stat.points THEN 1 ELSE 0 END) as wins,
                SUM(CASE WHEN team_stat.points < opponent_stat.points THEN 1 ELSE 0 END) as losses
            ')
            ->first();

        $team->wins = (int) ($record?->wins ?? 0);
        $team->losses = (int) ($record?->losses ?? 0);
        $team->save();
    }

    public static function headers(): array
    {
        return [
            'ocp-apim-subscription-key' => '747fa6900c6c4e89a58b81b72f36eb96',
            'Accept' => ' */*',
            'Accept-Encoding' => ' gzip, deflate, br, zstd',
            'Accept-Language' => ' es-ES,es;q=0.9',
            'Cache-Control' => ' no-cache',
            'Connection' => ' keep-alive',
            'Host' => ' stats.nba.com',
            'Origin' => ' https://www.nba.com',
            'Pragma' => ' no-cache',
            'Referer' => ' https://www.nba.com/',
            'Sec-Fetch-Dest' => ' empty',
            'Sec-Fetch-Mode' => ' cors',
            'Sec-Fetch-Site' => ' same-site',
            'User-Agent' => ' Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36',
            'sec-ch-ua' => '"Not(A:Brand";v="99", "Google Chrome";v="133", "Chromium";v="133"',
            'sec-ch-ua-mobile' => ' ?0',
            'sec-ch-ua-platform' => 'Windows',
        ];
    }
}
