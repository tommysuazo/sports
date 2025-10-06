<?php

namespace App\Services;

use App\Exceptions\KnownException;
use App\Models\NflGame;
use App\Models\NflPlayer;
use App\Models\NflPlayerStat;
use App\Models\NflTeam;
use App\Models\NflTeamStat;
use App\Models\NhlTeam;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NhlExternalService
{
    public function __construct(
    ) {
    }


    public static function getTeams()
    {
        $request = Http::get('https://api-web.nhle.com/v1/standings/2025-04-17');

        if (!$request->successful()) {
            throw new KnownException("Fallo en el retorno del listado de equipos de la NHL con la clase " . __CLASS__);
        }

        return $request->json();
    }

    public static function getTeamPlayers(NhlTeam $team)
    {
        $request = Http::get("https://api-web.nhle.com/v1/roster/{$team->code}/20252026");

        if (!$request->successful()) {
            throw new KnownException("Fallo en el retorno del listado del roster del equipo {$team->code} de la NHL");
        }

        return $request->json();
    }

    public static function getGameIdsByDate(string $date)
    {
        $request = Http::get("https://api-web.nhle.com/v1/score/{$date}");

        if (!$request->successful()) {
            throw new KnownException("Fallo en el retorno del listado de los juegos de NHL de la fecha {$date}");
        }

        return Collect($request->json('items.*.$ref'))->map(function ($url) {
            preg_match('/events\/(\d+)\?/', $url, $matches);
            return $matches[1] ?? null;
        })->filter()->values();
    }
    
    public static function getGame(string $gameId)
    {
        $request = Http::get("https://api-web.nhle.com/v1/gamecenter/{$gameId}/boxscore");

        if (!$request->successful()) {
            throw new KnownException("Fallo del juego de ID externo '{$gameId}' de NHL");
        }

        return $request->json();
    }

    public function importGamesByDate(string $date)
    {
        Log::info("Importing nhl games for date {$date}");

        $gameIds = self::getGameIdsByDate($date);

        foreach ($gameIds as $gameId) {
            $this->createGame($gameId);
        }
    }

    public function createGame($gameId)
    {
        $game = NflGame::where('external_id', $gameId)->withCount('stats')->first();

        if ($game && $game->team_stats_count > 0) {
            Log::info("NFL game {$gameId} already has team stats. Skipping.");
            return $game;
        }

        Log::info("Importing NFL game with external ID {$gameId}");
        
        $data = self::getGame($gameId);
        
        $isCompleted = $data['header']['competitions'][0]['status']['type']['completed'];

        try {
            DB::beginTransaction();
            
            $awayTeamIndex = $data['boxscore']['teams'][0]['homeAway'] === 'away' ? 0 : 1;
            $homeTeamIndex = $awayTeamIndex ? 0 : 1;

            $awayTeamExternalId = $data['boxscore']['teams'][$awayTeamIndex]['team']['id'];
            $homeTeamExternalId = $data['boxscore']['teams'][$homeTeamIndex]['team']['id'];

            $teams = NflTeam::whereIn('external_id', [$awayTeamExternalId, $homeTeamExternalId])->get();

            $awayTeam = $teams->firstWhere('external_id', $awayTeamExternalId);
            $homeTeam = $teams->firstWhere('external_id', $homeTeamExternalId);


            if ($game) {
                if ($game->is_completed) {
                    DB::rollBack();
                    return $game;
                }

                $game->is_completed = $isCompleted;
                $game->save();
            
            } else {
                $game = NflGame::create([
                    'external_id' => $gameId,
                    'season' => $data['header']['season']['year'],
                    'week' => $data['header']['week'],
                    'played_at' => Carbon::parse($data['header']['competitions'][0]['date']),
                    'away_team_id' => $awayTeam->id,
                    'home_team_id' => $homeTeam->id,
                    'is_completed' => $isCompleted,
                ]);
            }

            if (!$isCompleted) {
                Log::info("The NFL game with external ID {$gameId} has not been completed. Stored header only.");
                DB::commit();
                return $game;
            }

            $this->createTeamStat($game, $awayTeam, $data);

            $this->createTeamStat($game, $homeTeam, $data);
            
            DB::commit();

        } catch (\Throwable $th) {
            DB::rollBack();

            Log::warning("Fail to save NFL game with external ID {$gameId}");
            throw $th;
        }

        return $game;
    }


    public function createTeamStat(NflGame $game, NflTeam $team, array $data)
    {
        $isAway = $team->external_id == $data['boxscore']['teams'][0]['team']['id']
            ? $data['boxscore']['teams'][0]['homeAway'] === 'away'
            : $data['boxscore']['teams'][1]['homeAway'] === 'away';

        $stats = $team->external_id == $data['boxscore']['players'][0]['team']['id']
            ? $data['boxscore']['players'][0]['statistics']
            : $data['boxscore']['players'][1]['statistics'];

        $playerStats = $this->getPlayerStatFromStatistics($stats);

        $players = NflPlayer::whereIn('external_id', array_keys($playerStats))->get();
        
        $teamStatInsertion = [
            'passing_yards' => 0,
            'pass_completions' => 0,
            'pass_attempts' => 0,
            'receiving_yards' => 0,
            'rushing_yards' => 0,
            'carries' => 0,
            'sacks' => 0,
            'tackles' => 0,
        ];

        foreach ($playerStats as $externalId => $playerStat) {
            $player = $players->firstWhere('external_id', $externalId);

            if (!$player) {
                $player = NflPlayer::create([
                    'external_id' => $externalId,
                    'team_id' => $team->id,
                    'first_name' => $playerStat['first_name'],
                    'last_name' => $playerStat['last_name'],
                ]);
            }

            $stats = [
                'team_id' => $team->id,
                'is_away' => $isAway,
                'passing_yards' => $playerStat['passing_yards'] ?? 0,
                'pass_completions' => $playerStat['pass_completions'] ?? 0,
                'pass_attempts' => $playerStat['pass_attempts'] ?? 0,
                'receiving_yards' => $playerStat['receiving_yards'] ?? 0,
                'receptions' => $playerStat['receptions'] ?? 0,
                'receiving_targets' => $playerStat['receiving_targets'] ?? 0,
                'rushing_yards' => $playerStat['rushing_yards'] ?? 0,
                'carries' => $playerStat['carries'] ?? 0,
                'sacks' => $playerStat['sacks'] ?? 0,
                'tackles' => $playerStat['tackles'] ?? 0,
            ];

            NflPlayerStat::updateOrCreate([
                'game_id' => $game->id,
                'player_id' => $player->id,
            ], $stats);

            // Sumar al acumulado del equipo
            foreach ($teamStatInsertion as $key => $value) {
                $teamStatInsertion[$key] += $stats[$key];
            }
        }

        $teamScores = $data['header']['competitions'][0]['competitors'][0]['id'] === $team->external_id
            ? $data['header']['competitions'][0]['competitors'][0]
            : $data['header']['competitions'][0]['competitors'][1];

        NflTeamStat::updateOrCreate([
            'game_id' => $game->id,
            'team_id' => $team->id,
        ], [
            'is_away' => $isAway,
            'points_total' => (int) $teamScores['score'],
            'points_q1' => (int) $teamScores['linescores'][0]['displayValue'],
            'points_q2' => (int) $teamScores['linescores'][1]['displayValue'],
            'points_q3' => (int) $teamScores['linescores'][2]['displayValue'],
            'points_q4' => (int) $teamScores['linescores'][3]['displayValue'],
            'points_ot' => isset($teamScores['linescores'][4]) ? (int) $teamScores['linescores'][4]['displayValue'] : null,
            'total_yards' => $teamStatInsertion['passing_yards'] + $teamStatInsertion['rushing_yards'],
            'passing_yards' => $teamStatInsertion['passing_yards'],
            'pass_completions' => $teamStatInsertion['pass_completions'],
            'pass_attempts' => $teamStatInsertion['pass_attempts'],
            'rushing_yards' => $teamStatInsertion['rushing_yards'],
            'carries' => $teamStatInsertion['carries'],
            'sacks' => $teamStatInsertion['sacks'],
            'tackles' => $teamStatInsertion['tackles'],
        ]);

        
    }

    public function getPlayerStatFromStatistics(array $statTypes): array
    {
        $playerStats = [];

        foreach ($statTypes as $statType) {
            switch ($statType['name']) {
                case 'passing':
                    $this->getPlayerStatFromPassingStatistics($playerStats, $statType);
                    break;

                case 'rushing':
                    $this->getPlayerStatFromRushingStatistics($playerStats, $statType);
                    break;

                case 'receiving':
                    $this->getPlayerStatFromReceivingStatistics($playerStats, $statType);
                    break;

                case 'defensive':
                    $this->getPlayerStatFromDefensiveStatistics($playerStats, $statType);
                    break;

                default:
                    break;
            }

        }

        return $playerStats;
    }

    public function getPlayerStatFromPassingStatistics(array &$playerStats, $data)
    {
        // KEYS = [0 => Completions/Attempts, 1 => yards]

        foreach($data['athletes'] as $stat) {
            $playerExternalId = $stat['athlete']['id'];
            
            $passes = explode('/', $stat['stats'][0]);

            $playerStats[$playerExternalId]['passing_yards'] = $stat['stats'][1];
            $playerStats[$playerExternalId]['pass_completions'] = $passes[0];
            $playerStats[$playerExternalId]['pass_attempts'] = $passes[1];

            $playerStats[$playerExternalId]['first_name'] = $stat['athlete']['firstName'];
            $playerStats[$playerExternalId]['last_name'] = $stat['athlete']['lastName'];
        }
    }

    public function getPlayerStatFromRushingStatistics(array &$playerStats, $data)
    {
        // KEYS = [0 => Rushing Attempts, 1 => yards]

        foreach($data['athletes'] as $stat) {
            $playerExternalId = $stat['athlete']['id'];

            $playerStats[$playerExternalId]['rushing_yards'] = $stat['stats'][1];
            $playerStats[$playerExternalId]['carries'] = $stat['stats'][0];

            $playerStats[$playerExternalId]['first_name'] = $stat['athlete']['firstName'];
            $playerStats[$playerExternalId]['last_name'] = $stat['athlete']['lastName'];
        }
    }

    public function getPlayerStatFromReceivingStatistics(array &$playerStats, $data)
    {
        // KEYS = [0 => Receptions, 1 => Yards, 5 => Receiving Targets]

        foreach($data['athletes'] as $stat) {
            $playerExternalId = $stat['athlete']['id'];

            $playerStats[$playerExternalId]['receiving_yards'] = $stat['stats'][1];
            $playerStats[$playerExternalId]['receptions'] = $stat['stats'][0];
            $playerStats[$playerExternalId]['receiving_targets'] = $stat['stats'][5];

            $playerStats[$playerExternalId]['first_name'] = $stat['athlete']['firstName'];
            $playerStats[$playerExternalId]['last_name'] = $stat['athlete']['lastName'];
        }
    }

    public function getPlayerStatFromDefensiveStatistics(array &$playerStats, $data)
    {
        // KEYS = [0 => Tackles, 2 => Sacks]

        foreach($data['athletes'] as $stat) {
            $playerExternalId = $stat['athlete']['id'];

            $playerStats[$playerExternalId]['sacks'] = $stat['stats'][2];
            $playerStats[$playerExternalId]['tackles'] = $stat['stats'][0];

            $playerStats[$playerExternalId]['first_name'] = $stat['athlete']['firstName'];
            $playerStats[$playerExternalId]['last_name'] = $stat['athlete']['lastName'];
        }
    }

    public function test()
    {
        /*
        //RUTAS

        // LISTADO EQUIPOS 
        https://api.nfl.com/experience/v1/teams?season=2025

        // LISTADO DE JUGACORES


        // lISTADO DE JUEGOS POR SEMANA 
        https://api.nfl.com/football/v2/stats/live/game-summaries?season=2024&seasonType=REG&week=1

        // DETALLE DE LOS SCORES DE LOS EQUIPOS EN UN JUEGO
        https://api.nfl.com/experience/v2/gamedetails/7d4019ca-1312-11ef-afd1-646009f18b2e?includeDriveChart=false&includeReplays=true&includeStandings=false&includeTaggedVideos=true

        // DETALLE DEL SCORES DE LOS JUGADORES EN UN JUEGO
        https://api.nfl.com/football/v2/stats/live/player-statistics/7d4019ca-1312-11ef-afd1-646009f18b2e


        // LISTADO ALINEACIONES
        */
    }

    public function data()
    {

    }


}
