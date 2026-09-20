<?php

namespace App\Services;

use App\Enums\NflWeekEnum;
use App\Models\NflGame;
use App\Models\NflGameMarket;
use App\Models\NflPlayer;
use App\Models\NflPlayerMarket;
use App\Models\NflTeam;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

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

    private const GFM_GAMES_ENDPOINT = 'https://bv2-us.digitalsportstech.com/api/gfm/gamesByGfm';
    private const PLAYER_MARKET_ENDPOINT = 'https://bv2-us.digitalsportstech.com/api/dfm/marketsByOu';
    private const TEAM_MARKET_ENDPOINT = 'https://bv2-us.digitalsportstech.com/api/sgmMarkets/gfm/grouped';
    private const SPORTSBOOK_ALIAS = 'juancito';

    public function __construct(
        protected DigitalSportsTechClient $digitalSportsTechClient,
    ) {
    }

    public function getLiveMarkets($week = null): Collection
    {
        $targetWeek = is_null($week)
            ? NflWeekEnum::current()
            : NflWeekEnum::getWeek((int) $week);

        if (!$targetWeek) {
            Log::warning('No se pudo determinar la semana actual de la NFL para obtener mercados');

            return collect();
        }

        return NflGame::with(['homeTeam', 'awayTeam', 'market', 'playerMarkets',])
            ->where('season', $targetWeek->seasonYear())
            ->where('week', $targetWeek->value)
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

            $marketPlayers = $this->digitalSportsTechClient->get('player', [
                'leagueId' => 142,
                'teamId' => $team->market_id,
            ]);

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
        $gamesPayload = $this->fetchGfmGames();

        if (empty($gamesPayload)) {
            Log::info('Sin datos de GFM para sincronizar mercados NFL');
            return;
        }

        if ($marketId) {
            $gamesPayload = array_values(array_filter(
                $gamesPayload,
                fn (array $game) => $this->gameMatchesMarketId($game, $marketId)
            ));

            if (empty($gamesPayload)) {
                Log::warning('No se encontró juego NFL para el market_id solicitado', [
                    'market_id' => $marketId,
                ]);
                return;
            }
        }

        $teams = NflTeam::all();
        $playersByMarketId = NflPlayer::whereNotNull('market_id')->get()->keyBy('market_id');

        if ($playersByMarketId->isEmpty()) {
            Log::warning('No hay jugadores con market_id asignado para sincronizar mercados NFL');
        }

        foreach ($gamesPayload as $gamePayload) {
            $resolvedMarketId = $this->extractMarketId($gamePayload);

            if (!$resolvedMarketId) {
                continue;
            }

            if ($marketId && (string) $resolvedMarketId !== (string) $marketId) {
                continue;
            }

            $scheduledAt = $this->parseGameDate($gamePayload['date'] ?? null);
            [$homeTeam, $awayTeam] = $this->resolveTeams($teams, $gamePayload);

            if (!$homeTeam || !$awayTeam || !$scheduledAt) {
                Log::warning('No se pudo resolver equipos o fecha para juego NFL', [
                    'market_id' => $resolvedMarketId,
                    'payload' => $gamePayload,
                ]);
                continue;
            }

            $game = $this->findMatchingGame($homeTeam->id, $awayTeam->id, $scheduledAt);

            if (!$game) {
                Log::warning('Juego NFL no encontrado para sincronización de mercados', [
                    'market_id' => $resolvedMarketId,
                    'home_team_id' => $homeTeam->id,
                    'away_team_id' => $awayTeam->id,
                    'scheduled_at' => $scheduledAt->toIso8601String(),
                ]);
                continue;
            }

            if ($game->market_id !== (string) $resolvedMarketId) {
                $game->update(['market_id' => (string) $resolvedMarketId]);
            }

            $teamPayload = $this->fetchTeamMarketPayload($resolvedMarketId);

            if (!$teamPayload) {
                Log::info('Sin payload de mercados de equipo para juego NFL', [
                    'market_id' => $resolvedMarketId,
                    'nfl_game_id' => $game->id,
                ]);
            } else {
                $this->syncGameMarketData($game, $resolvedMarketId, $teamPayload);
            }

            if ($playersByMarketId->isNotEmpty()) {
                $this->syncPlayerMarketData($game, $resolvedMarketId, $playersByMarketId);
            }
        }
    }

    private function fetchGfmGames(): array
    {
        try {
            $response = $this->digitalSportsTechClient->get(self::GFM_GAMES_ENDPOINT, [
                'sb' => self::SPORTSBOOK_ALIAS,
                'league' => 'nfl',
            ], 20);

            if (!$response->successful()) {
                Log::warning('Error HTTP al obtener juegos NFL con mercados', [
                    'status' => $response->status(),
                ]);
                return [];
            }

            $payload = $response->json();

            return is_array($payload) ? $payload : [];
        } catch (\Throwable $exception) {
            Log::error('Excepción al obtener juegos NFL con mercados', [
                'message' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    private function extractMarketId(array $gamePayload): ?string
    {
        $providers = $gamePayload['providers'] ?? null;

        if (!is_array($providers)) {
            return null;
        }

        foreach ($providers as $provider) {
            if (!empty($provider['id']) && ($provider['isPrimary'] ?? false)) {
                return (string) $provider['id'];
            }
        }

        $fallback = data_get($providers, '0.id');

        return $fallback ? (string) $fallback : null;
    }

    private function gameMatchesMarketId(array $gamePayload, string $targetMarketId): bool
    {
        $providers = $gamePayload['providers'] ?? [];

        foreach ($providers as $provider) {
            if (isset($provider['id']) && (string) $provider['id'] === (string) $targetMarketId) {
                return true;
            }
        }

        return false;
    }

    private function parseGameDate(?string $date): ?Carbon
    {
        if (!$date) {
            return null;
        }

        try {
            return Carbon::parse($date)->setTimezone('UTC');
        } catch (\Throwable $exception) {
            Log::warning('Fecha inválida en payload de juego NFL', [
                'date' => $date,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /** @return array{0:?NflTeam,1:?NflTeam} */
    private function resolveTeams(Collection $teams, array $gamePayload): array
    {
        $homePayload = data_get($gamePayload, 'team1.0', []);
        $awayPayload = data_get($gamePayload, 'team2.0', []);

        return [
            $this->findTeam($teams, $homePayload),
            $this->findTeam($teams, $awayPayload),
        ];
    }

    private function findTeam(Collection $teams, array $teamPayload): ?NflTeam
    {
        $providerIds = collect($teamPayload['providers'] ?? [])
            ->pluck('id')
            ->filter()
            ->map(static fn ($id) => (string) $id);

        if ($providerIds->isNotEmpty()) {
            $team = $teams->first(function (NflTeam $team) use ($providerIds) {
                return $team->market_id !== null && $providerIds->contains((string) $team->market_id);
            });

            if ($team) {
                return $team;
            }
        }

        $abbreviation = strtoupper((string) ($teamPayload['abbreviation'] ?? ''));

        if ($abbreviation !== '') {
            $team = $teams->first(function (NflTeam $team) use ($abbreviation) {
                return strtoupper($team->code) === $abbreviation;
            });

            if ($team) {
                return $team;
            }
        }

        $title = $teamPayload['title'] ?? null;

        if ($title) {
            $normalizedTitle = $this->normalizeName($title);

            $team = $teams->first(function (NflTeam $team) use ($normalizedTitle) {
                return $this->normalizeName($team->city . ' ' . $team->name) === $normalizedTitle;
            });

            if ($team) {
                return $team;
            }
        }

        return null;
    }

    private function normalizeName(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return $normalized ?? '';
    }

    private function findMatchingGame(int $homeTeamId, int $awayTeamId, Carbon $scheduledAt): ?NflGame
    {
        return NflGame::where('home_team_id', $homeTeamId)
            ->where('away_team_id', $awayTeamId)
            ->whereDate('played_at', $scheduledAt->toDateString())
            ->first();
    }

    private function fetchTeamMarketPayload(string $marketId): ?array
    {
        try {
            $response = $this->digitalSportsTechClient->get(self::TEAM_MARKET_ENDPOINT, [
                'sb' => self::SPORTSBOOK_ALIAS,
                'legacy' => 1,
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
            $response = $this->digitalSportsTechClient->get(self::PLAYER_MARKET_ENDPOINT, [
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

    private function syncGameMarketData(NflGame $game, string $marketId, array $teamPayload): void
    {
        $toWin = collect($teamPayload)->first(function ($entry) {
            return isset($entry['statistic']) && strtolower($entry['statistic']) === 'to win';
        });

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

    private function normalizeHandicapValue(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $numeric = (float) $value;

        return number_format(abs($numeric), 1, '.', '');
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
        $statisticData = collect($payload)->first(function ($entry) use ($statistic) {
            $entryStatistic = $entry['statistic'] ?? null;

            return $entryStatistic && strtolower($entryStatistic) === strtolower($statistic);
        });

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
