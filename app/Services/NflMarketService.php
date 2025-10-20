<?php

namespace App\Services;

use App\Enums\NflWeekEnum;
use App\Models\NflGame;
use App\Models\NflGameMarket;
use App\Models\NflPlayer;
use App\Models\NflPlayerMarket;
use App\Models\NflTeam;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class NflMarketService
{
    private const PLAYER_STAT_COLUMN_MAP = [
        'Passing Yards' => 'passing_yards',
        'Pass Completions' => 'pass_completions',
        'Pass Attempts' => 'pass_attempts',
        'Rushing Yards' => 'rushing_yards',
        'Carries' => 'carries',
        'Receiving Yards' => 'receiving_yards',
        'Receptions' => 'receptions',
        'Tackles' => 'tackles',
        'Sacks' => 'sacks',
    ];

    private const DISCOVERY_STATISTICS = [
        'Passing Yards',
        'Pass Completions',
        'Pass Attempts',
        'Rushing Yards',
        'Carries',
        'Receiving Yards',
        'Receptions',
        'Tackles',
        'Sacks',
    ];

    private const PLAYER_GAMES_ENDPOINT = 'https://bv2-us.digitalsportstech.com/api/dfm/gamesByOu';
    private const PLAYER_MARKET_ENDPOINT = 'https://bv2-us.digitalsportstech.com/api/dfm/marketsByOu';
    private const TEAM_MARKET_ENDPOINT = 'https://bv2-us.digitalsportstech.com/api/sgmMarkets/gfm/grouped';
    private const SPORTSBOOK_ALIAS = 'juancito';

    public function __construct(
    ) {
    }

    

    public function getLiveMarkets($week = null): Collection
    {
        return NflGame::with(['homeTeam', 'awayTeam', 'market', 'playerMarkets',])
            ->where('week', $week ?? NflWeekEnum::current()->value)
            ->get();
    }

    public function getMatchups($week = null): Collection
    {
        $targetWeek = is_null($week) 
            ? NflWeekEnum::current()
            : NflWeekEnum::getWeek((int) $week);

        if (!$targetWeek) {
            Log::warning('No se pudo determinar la semana actual de la NFL para obtener enfrentamientos');
            return collect();
        }

        return NflGame::query()
            ->with([
                'awayTeam' => function ($query) {
                    $query->with([
                        'players' => function ($playerQuery) {
                            $playerQuery->whereNotNull('nfl_players.market_id')
                                ->with(['stats' => function ($scoreQuery) {
                                    $scoreQuery->take(5)->orderByDesc('nfl_player_stats.game_id');
                                }]);
                        }
                    ]);
                },
                'homeTeam' => function ($query) {
                    $query->with([
                        'players' => function ($playerQuery) {
                            $playerQuery->whereNotNull('nfl_players.market_id')
                                ->with(['stats' => function ($scoreQuery) {
                                    $scoreQuery->take(5)->orderByDesc('nfl_player_stats.game_id');
                                }]);
                        }
                    ]);
                },
            ])
            ->where('season', $targetWeek->seasonYear())
            ->whereBetween('played_at', [$targetWeek->startDate(), $targetWeek->endDate()])
            ->orderBy('played_at')
            ->where('is_completed', false)
            ->get();
    }

    public function syncPlayers()
    {
        $teams = NflTeam::with('players')->get();

        foreach ($teams as $team) {
            Log::info("Procesando equipo {$team->name}");
            $players = $team->players;

            $marketPlayers = Http::get("https://bv2-us.digitalsportstech.com/api/player?leagueId=142&teamId={$team->market_id}");

            $marketPlayers = collect($marketPlayers->json())->map(fn ($player) => collect($player));

            $activePlayers = $marketPlayers->where('isActive', true);
            
            $inactivePlayers = $marketPlayers->where('isActive', false);

            foreach($activePlayers as $marketPlayer) {
                Log::info("Procesando jugador de MERCADO con nombre {$marketPlayer['name']} en {$team->name}");

                $player = $players->first(fn ($p) => $p->full_name === $marketPlayer['name']);
                
                if (!$player) {
                    $name = explode(' ', $marketPlayer['name']);

                    $player = $players->filter(
                        fn($player) => $player->first_name === $name[0] && strpos($player->last_name, substr($name[1], 0, 3)) === 0
                    )->first();

                    if (!$player) {
                        $player = $players->filter(
                            fn($player) => $player->last_name === $name[1] && strpos($player->first_name, substr($name[0], 0, 3)) === 0
                        )->first();
                    }
                }

                if ($player) {
                    $player->update(['market_id' => $marketPlayer['id']]);
                    $player->market_id = $marketPlayer['id'];

                } elseif (!$player) {
                    Log::info("No se encontró jugador para {$marketPlayer['name']} en {$team->name}");
                }
            }

            foreach($players->whereNull('market_id') as $player) {
                Log::info("Procesando jugador EXTERNAL con nombre {$player->full_name} en {$team->name}");

                $marketPlayer = $inactivePlayers->first(fn ($mp) => $mp['name'] === $player->full_name);

                if ($marketPlayer) {
                    Log::info("Encontrado jugador inactivo en mercado para {$player->full_name} en {$team->name}");
                }
                
                if (!$marketPlayer) {
                    $marketPlayer = $inactivePlayers->filter(function($mp) use ($player) {
                        $name = explode(' ', $mp['name']);
                        return $player->first_name === $name[0] && strpos($player->last_name, substr($name[1], 0, 3)) === 0;
                    })->first();

                    if (!$marketPlayer) {
                        $marketPlayer = $inactivePlayers->filter(function($mp) use ($player) {
                            $name = explode(' ', $mp['name']);
                            return $player->last_name === $name[0] && strpos($player->first_name, substr($name[1], 0, 3)) === 0;
                        })->first();
                    }
                }

                if ($marketPlayer && !$player->market_id) {
                    $player->update(['market_id' => $marketPlayer['id']]);
                } elseif (!$marketPlayer) {
                    Log::info("No se encontró jugador para {$player->full_name} en {$team->name}");
                }
            }
        }
    }

    public function syncMarkets(?string $marketId = null): void
    {
        $gameIds = $marketId ? [$marketId] : $this->fetchAllGameIds();

        if (empty($gameIds)) {
            Log::warning('Sin gameIds para sincronizar mercados NFL', ['market_id' => $marketId]);
            return;
        }

        $playersByMarketId = NflPlayer::whereNotNull('market_id')->get()->keyBy('market_id');

        if ($playersByMarketId->isEmpty()) {
            Log::warning('No hay jugadores con market_id asignado para sincronizar mercados NFL');
        }

        foreach ($gameIds as $targetMarketId) {
            $teamPayload = $this->fetchTeamMarketPayload($targetMarketId);

            if (!$teamPayload) {
                Log::warning('No se pudo obtener payload de equipos', ['game_id' => $targetMarketId]);
                continue;
            }

            $game = $this->resolveGameFromTeamPayload($targetMarketId, $teamPayload);

            if (!$game) {
                Log::warning('Juego NFL no encontrado para mercado', ['game_id' => $targetMarketId]);
                continue;
            }

            if ($game->market_id !== $targetMarketId) {
                $game->update(['market_id' => $targetMarketId]);
            }

            $this->syncGameMarketData($game, $targetMarketId, $teamPayload);

            if ($playersByMarketId->isNotEmpty()) {
                $this->syncPlayerMarketData($game, $targetMarketId, $playersByMarketId);
            }
        }
    }

    private function fetchAllGameIds(): array
    {
        $gameIds = [];

        foreach (self::DISCOVERY_STATISTICS as $statistic) {
            try {
                $response = Http::timeout(15)->get(self::PLAYER_GAMES_ENDPOINT, [
                    'gameId' => 'null',
                    'statistic' => $statistic,
                    'league' => 'nfl',
                ]);

                if (!$response->successful()) {
                    Log::warning('Error al obtener gameIds', [
                        'statistic' => $statistic,
                        'status' => $response->status(),
                    ]);
                    continue;
                }

                $payload = $response->json();

                if (!is_array($payload)) {
                    Log::warning('Respuesta inválida al obtener gameIds', ['statistic' => $statistic]);
                    continue;
                }

                $gameIds = array_merge($gameIds, $this->extractGameIdsFromResponse($payload));
            } catch (\Throwable $exception) {
                Log::error('Excepción al obtener gameIds', [
                    'statistic' => $statistic,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return array_values(array_unique($gameIds));
    }

    private function fetchTeamMarketPayload(string $marketId): ?array
    {
        try {
            $response = Http::timeout(15)->get(self::TEAM_MARKET_ENDPOINT, [
                'sb' => self::SPORTSBOOK_ALIAS,
                'gameId' => $marketId,
            ]);

            if (!$response->successful()) {
                Log::warning('Error HTTP al obtener mercado de equipos', [
                    'game_id' => $marketId,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $payload = $response->json();

            return is_array($payload) ? $payload : null;
        } catch (\Throwable $exception) {
            Log::error('Excepción al obtener mercado de equipos', [
                'game_id' => $marketId,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function fetchPlayerStatPayloads(string $marketId): array
    {
        $statBlocks = [];

        foreach (self::PLAYER_STAT_COLUMN_MAP as $statistic => $column) {
            $payload = $this->fetchPlayerMarketPayload($marketId, $statistic);

            if ($payload === null) {
                continue;
            }

            $statBlocks[] = [
                'statistic' => $statistic,
                'column' => $column,
                'payload' => $payload,
            ];
        }

        return $statBlocks;
    }

    private function fetchPlayerMarketPayload(string $marketId, string $statistic): ?array
    {
        try {
            $response = Http::timeout(15)->get(self::PLAYER_MARKET_ENDPOINT, [
                'sb' => self::SPORTSBOOK_ALIAS,
                'gameId' => $marketId,
                'statistic' => $statistic,
            ]);

            if (!$response->successful()) {
                Log::warning('Error HTTP al obtener mercado de jugadores', [
                    'game_id' => $marketId,
                    'statistic' => $statistic,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $payload = $response->json();

            return is_array($payload) ? $payload : null;
        } catch (\Throwable $exception) {
            Log::error('Excepción al obtener mercado de jugadores', [
                'game_id' => $marketId,
                'statistic' => $statistic,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function resolveGameFromTeamPayload(string $marketId, array $teamPayload): ?NflGame
    {
        $game = NflGame::where('market_id', $marketId)->first();

        if ($game) {
            return $game;
        }

        $toWin = collect($teamPayload)->firstWhere('statistic', 'to win');

        if (empty($toWin['markets'])) {
            return null;
        }

        $gameInfo = $this->extractGameInfo($toWin['markets']);

        if (!$gameInfo) {
            return null;
        }

        [$homeMarketId, $awayMarketId] = $gameInfo;

        $homeTeam = $homeMarketId ? NflTeam::where('market_id', $homeMarketId)->first() : null;
        $awayTeam = $awayMarketId ? NflTeam::where('market_id', $awayMarketId)->first() : null;

        if (!$homeTeam || !$awayTeam) {
            return null;
        }

        return NflGame::where('home_team_id', $homeTeam->id)
            ->where('away_team_id', $awayTeam->id)
            ->first();
    }

    private function syncGameMarketData(NflGame $game, string $marketId, array $teamPayload): void
    {
        $toWin = collect($teamPayload)->firstWhere('statistic', 'to win');

        if (empty($toWin['markets'])) {
            Log::warning('Mercado de equipos sin estadística to win', [
                'game_id' => $marketId,
                'nfl_game_id' => $game->id,
            ]);
            return;
        }

        $favoriteTeamId = $this->determineFavoriteTeamId(
            $toWin['markets'],
            $game->home_team_id,
            $game->away_team_id
        );

        $handicap = $this->normalizeHandicapValue($this->extractPrincipalValue($teamPayload, 'handicap win'));
        $totalPoints = $this->extractPrincipalValue($teamPayload, 'total points');
        $firstHalfHandicap = $this->normalizeHandicapValue($this->extractPrincipalValue($teamPayload, '1st half handicap win'));
        $firstHalfPoints = $this->extractPrincipalValue($teamPayload, '1st half total points');
        $awaySoloPoints = $this->extractPrincipalValue($teamPayload, 'away team total points');
        $homeSoloPoints = $this->extractPrincipalValue($teamPayload, 'home team total points');

        $candidateValues = [
            'favorite_team_id' => $favoriteTeamId,
            'handicap' => $handicap,
            'total_points' => $totalPoints,
            'first_half_handicap' => $firstHalfHandicap,
            'first_half_points' => $firstHalfPoints,
            'away_team_solo_points' => $awaySoloPoints,
            'home_team_solo_points' => $homeSoloPoints,
        ];

        $updates = array_filter(
            $candidateValues,
            static fn ($value) => $value !== null
        );

        if (empty($updates)) {
            Log::info('Sin valores nuevos para mercados de equipos', [
                'game_id' => $marketId,
                'nfl_game_id' => $game->id,
            ]);
            return;
        }

        NflGameMarket::updateOrCreate(['game_id' => $game->id], $updates);
    }

    private function syncPlayerMarketData(NflGame $game, string $marketId, Collection $playersByMarketId): void
    {
        $statBlocks = $this->fetchPlayerStatPayloads($marketId);

        if (empty($statBlocks)) {
            Log::info('Sin mercados de jugadores disponibles', [
                'game_id' => $marketId,
                'nfl_game_id' => $game->id,
            ]);
            return;
        }

        $playerRows = [];

        foreach ($statBlocks as $block) {
            foreach ($block['payload'] as $statEntry) {
                foreach ($statEntry['players'] ?? [] as $playerBlock) {
                    $playerMarketId = $playerBlock['id'] ?? null;

                    if (!$playerMarketId) {
                        continue;
                    }

                    $value = $this->selectBestPlayerValue($playerBlock['markets'] ?? []);

                    if ($value === null) {
                        continue;
                    }

                    $player = $playersByMarketId->get($playerMarketId);

                    if (!$player) {
                        continue;
                    }

                    if (!isset($playerRows[$player->id])) {
                        $playerRows[$player->id] = [
                            'game_id' => $game->id,
                            'player_id' => $player->id,
                        ];
                    }

                    $playerRows[$player->id][$block['column']] = $value;
                }
            }
        }

        if (empty($playerRows)) {
            Log::info('No se encontraron valores nuevos para jugadores', [
                'game_id' => $marketId,
                'nfl_game_id' => $game->id,
            ]);
            return;
        }

        foreach ($playerRows as $row) {
            $attributes = [
                'game_id' => $row['game_id'],
                'player_id' => $row['player_id'],
            ];

            $updates = [];

            foreach (self::PLAYER_STAT_COLUMN_MAP as $column) {
                if (array_key_exists($column, $row)) {
                    $updates[$column] = $row[$column];
                }
            }

            if (!empty($updates)) {
                NflPlayerMarket::updateOrCreate($attributes, $updates);
            }
        }
    }

    private function extractGameIdsFromResponse(array $jsonData): array
    {
        $gameIds = [];

        foreach ($jsonData as $game) {
            if (!is_array($game)) {
                continue;
            }

            $providers = $game['providers'] ?? null;

            if (!is_array($providers)) {
                continue;
            }

            foreach ($providers as $provider) {
                if (isset($provider['id'])) {
                    $gameIds[] = (string) $provider['id'];
                }
            }
        }

        return $gameIds;
    }

    private function normalizeHandicapValue(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $numeric = (float) $value;

        return number_format(abs($numeric), 1, '.', '');
    }

    private function extractGameInfo(array $markets): ?array
    {
        foreach ($markets as $entry) {
            $game = $entry['game'] ?? null;

            if (!$game) {
                continue;
            }

            $homeMarketId = data_get($game, 'team1.0.providers.0.id');
            $awayMarketId = data_get($game, 'team2.0.providers.0.id');

            if ($homeMarketId && $awayMarketId) {
                return [$homeMarketId, $awayMarketId];
            }
        }

        return null;
    }

    private function determineFavoriteTeamId(array $markets, int $homeTeamId, int $awayTeamId): ?int
    {
        $filtered = collect($markets)->filter(function ($entry) {
            $type = data_get($entry, 'condition.0.type');

            return in_array($type, ['1', '2'], true) && isset($entry['odds']);
        });

        if ($filtered->isEmpty()) {
            return null;
        }

        $favorite = $filtered->sortBy('odds')->first();
        $type = data_get($favorite, 'condition.0.type');

        return $type === '1' ? $homeTeamId : ($type === '2' ? $awayTeamId : null);
    }

    private function extractPrincipalValue(array $payload, string $statistic): ?string
    {
        $statisticData = collect($payload)->firstWhere('statistic', $statistic);

        if (empty($statisticData['markets'])) {
            return null;
        }

        $groups = collect($statisticData['markets'])
            ->filter(function ($entry) {
                $type = data_get($entry, 'condition.0.type');

                return !in_array($type, ['x', 'equal'], true);
            })
            ->groupBy(function ($entry) {
                return (string) data_get($entry, 'condition.0.value');
            });

        $bestGroup = null;
        $smallestDiff = null;
        $bestValue = null;

        foreach ($groups as $value => $entries) {
            if ($value === '' || $value === null || $entries->count() < 2) {
                continue;
            }

            $odds = $entries->pluck('odds')->filter()->map(fn ($odd) => (float) $odd);

            if ($odds->count() < 2) {
                continue;
            }

            $diff = $odds->max() - $odds->min();

            if ($smallestDiff === null || $diff < $smallestDiff) {
                $smallestDiff = $diff;
                $bestGroup = $value;
                $bestValue = data_get($entries->first(), 'condition.0.value');
            }
        }

        if ($bestGroup === null) {
            return null;
        }

        return $bestValue !== null ? (string) $bestValue : (string) $bestGroup;
    }

    private function selectBestPlayerValue(array $markets): ?string
    {
        $groups = collect($markets)
            ->filter(function ($entry) {
                return isset($entry['value']) && isset($entry['odds']);
            })
            ->groupBy(function ($entry) {
                return (string) $entry['value'];
            });

        $bestEntry = null;
        $smallestDiff = null;

        foreach ($groups as $entries) {
            if ($entries->count() < 2) {
                continue;
            }

            $odds = $entries->pluck('odds')->filter()->map(fn ($odd) => (float) $odd);

            if ($odds->count() < 2) {
                continue;
            }

            $diff = $odds->max() - $odds->min();

            if ($smallestDiff === null || $diff < $smallestDiff) {
                $smallestDiff = $diff;
                $bestEntry = $entries->first();
            }
        }

        if (!$bestEntry || !isset($bestEntry['value'])) {
            return null;
        }

        return $this->formatDecimalValue($bestEntry['value']);
    }

    private function formatDecimalValue(float|int|string $value): string
    {
        $numeric = (float) $value;

        return number_format($numeric, 1, '.', '');
    }
}
