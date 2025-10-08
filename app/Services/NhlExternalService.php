<?php

namespace App\Services;

use App\Exceptions\KnownException;
use App\Models\NhlGame;
use App\Models\NhlPlayer;
use App\Models\NhlPlayerStat;
use App\Models\NhlTeam;
use App\Models\NhlTeamStat;
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

        return collect($request->json('games', []))
            ->map(fn ($game) => $game['id'] ?? $game['gamePk'] ?? null)
            ->filter()
            ->values();
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
        $game = NhlGame::where('external_id', $gameId)->withCount('stats')->first();

        if ($game && $game->team_stats_count > 0) {
            Log::info("NHL game {$gameId} already has team stats. Skipping.");
            return $game;
        }

        Log::info("Importing NHL game with external ID {$gameId}");

        $data = self::getGame($gameId);
        $isCompleted = $this->isCompletedGame($data);

        try {
            DB::beginTransaction();

            $awayTeam = NhlTeam::where('code', $data['awayTeam']['abbrev'])->first();
            $homeTeam = NhlTeam::where('code', $data['homeTeam']['abbrev'])->first();

            if (!$awayTeam || !$homeTeam) {
                throw new KnownException("No se encontraron los equipos para el juego {$gameId}");
            }

            if ($game && $game->is_completed) {
                DB::rollBack();
                return $game;
            }

            $gameAttributes = [
                'season' => $data['season'] ?? null,
                'start_at' => $data['gameDate'],
                'away_team_id' => $awayTeam->id,
                'home_team_id' => $homeTeam->id,
                'is_completed' => $isCompleted,
                'winner_team_id' => $this->getWinnerTeamId($awayTeam, $homeTeam, $data),
            ];

            if ($game) {
                $game->fill($gameAttributes);
                $game->save();
            } else {
                $game = NhlGame::create(array_merge([
                    'external_id' => $gameId,
                ], $gameAttributes));
            }

            if (!$isCompleted) {
                Log::info("El juego NHL {$gameId} aún no finaliza. Solo se guardó la cabecera.");
                DB::commit();
                return $game;
            }

            $this->createTeamStat($game, $awayTeam, $data);
            $this->createTeamStat($game, $homeTeam, $data);

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::warning("Fallo al guardar el juego NHL con ID externo {$gameId}");
            throw $th;
        }

        return $game;
    }


    public function createTeamStat(NhlGame $game, NhlTeam $team, array $data)
    {
        $teamSide = $this->getTeamSide($team, $data);

        if (!$teamSide) {
            Log::warning("No se pudo determinar el lado del equipo {$team->code} en el juego {$game->external_id}");
            return;
        }

        $teamScoreData = $data[$teamSide] ?? [];
        $playerStatsByGame = $data['playerByGameStats'][$teamSide] ?? [];

        $playersData = $this->flattenPlayerStats($playerStatsByGame);

        foreach ($playersData as $playerData) {
            $this->upsertPlayerStat($game, $team, $playerData);
        }

        $this->upsertTeamStat(
            $game,
            $team,
            (int) ($teamScoreData['score'] ?? 0),
            (int) ($teamScoreData['sog'] ?? 0)
        );
    }

    protected function isCompletedGame(array $data): bool
    {
        $state = $data['gameState'] ?? null;

        return in_array($state, ['FINAL', 'OFF', 'COMPLETE'], true)
            || (($data['clock']['secondsRemaining'] ?? 1) === 0 && ($data['periodDescriptor']['number'] ?? 0) >= 3);
    }

    protected function getWinnerTeamId(NhlTeam $awayTeam, NhlTeam $homeTeam, array $data): ?int
    {
        $awayScore = (int) ($data['awayTeam']['score'] ?? 0);
        $homeScore = (int) ($data['homeTeam']['score'] ?? 0);

        if ($awayScore === $homeScore) {
            return null;
        }

        return $awayScore > $homeScore ? $awayTeam->id : $homeTeam->id;
    }

    protected function getTeamSide(NhlTeam $team, array $data): ?string
    {
        $awayAbbrev = $data['awayTeam']['abbrev'] ?? null;
        $homeAbbrev = $data['homeTeam']['abbrev'] ?? null;

        if ($team->code === $awayAbbrev) {
            return 'awayTeam';
        }

        if ($team->code === $homeAbbrev) {
            return 'homeTeam';
        }

        if (!empty($data['awayTeam']['id']) && $team->external_id == $data['awayTeam']['id']) {
            return 'awayTeam';
        }

        if (!empty($data['homeTeam']['id']) && $team->external_id == $data['homeTeam']['id']) {
            return 'homeTeam';
        }

        return null;
    }

    protected function flattenPlayerStats(array $playerStatsByGame): array
    {
        $players = [];

        foreach (['forwards', 'defense', 'goalies'] as $group) {
            foreach ($playerStatsByGame[$group] ?? [] as $playerData) {
                $players[] = $playerData;
            }
        }

        return $players;
    }

    protected function upsertPlayerStat(NhlGame $game, NhlTeam $team, array $playerData): void
    {
        $timeOnIce = $this->parseTimeToMinutes($playerData['toi'] ?? null);

        if (!$timeOnIce) {
            return;
        }

        $player = $this->resolvePlayer($team, $playerData);

        if (!$player) {
            return;
        }

        $isGoalie = ($playerData['position'] ?? null) === 'G';

        $stat = NhlPlayerStat::firstOrNew([
            'game_id' => $game->id,
            'player_id' => $player->id,
        ]);

        $stat->is_starter = $this->isStarter($playerData);
        $stat->time = $timeOnIce;
        $stat->goals = (int) ($playerData['goals'] ?? 0);
        $stat->shots = (int) ($isGoalie ? ($playerData['shotsAgainst'] ?? 0) : ($playerData['sog'] ?? 0));
        $stat->assists = (int) ($playerData['assists'] ?? 0);
        $stat->points = (int) ($playerData['points'] ?? 0);
        $stat->saves = (int) ($playerData['saves'] ?? ($this->extractSavesFromShotsAgainst($playerData) ?? 0));
        $stat->save();
    }

    protected function resolvePlayer(NhlTeam $team, array $playerData): ?NhlPlayer
    {
        $externalId = $playerData['playerId'] ?? null;

        if (!$externalId) {
            return null;
        }

        $player = NhlPlayer::where('external_id', $externalId)->first();

        if ($player) {
            return $player;
        }

        $name = $playerData['name']['default'] ?? '';
        [$firstName, $lastName] = $this->splitName($name);

        return NhlPlayer::create([
            'external_id' => $externalId,
            'team_id' => $team->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'position' => $playerData['position'] ?? null,
        ]);
    }

    protected function splitName(string $fullName): array
    {
        $fullName = trim($fullName);

        if ($fullName === '') {
            return ['', ''];
        }

        $parts = preg_split('/\s+/', $fullName);

        $first = array_shift($parts) ?? '';
        $last = implode(' ', $parts);

        return [$first, $last];
    }

    protected function parseTimeToMinutes(?string $time): int
    {
        if (!$time) {
            return 0;
        }

        if (!str_contains($time, ':')) {
            return (int) $time;
        }

        [$minutes, $seconds] = array_pad(explode(':', $time), 2, '0');

        $total = ((int) $minutes) + (int) floor(((int) $seconds) / 60);

        return max(0, min(255, $total));
    }

    protected function extractSavesFromShotsAgainst(array $playerData): ?int
    {
        if (empty($playerData['saveShotsAgainst'])) {
            return null;
        }

        $parts = explode('/', $playerData['saveShotsAgainst']);

        if (count($parts) !== 2) {
            return null;
        }

        return (int) $parts[0];
    }

    protected function isStarter(array $playerData): bool
    {
        if (array_key_exists('starter', $playerData)) {
            return (bool) $playerData['starter'];
        }

        return $this->parseTimeToMinutes($playerData['toi'] ?? null) > 0;
    }

    protected function upsertTeamStat(NhlGame $game, NhlTeam $team, int $goals, int $shots): void
    {
        $teamStat = NhlTeamStat::firstOrNew([
            'game_id' => $game->id,
            'team_id' => $team->id,
        ]);

        $teamStat->goals = $goals;
        $teamStat->shots = $shots;
        $teamStat->save();
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
