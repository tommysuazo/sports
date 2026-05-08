<?php

namespace App\Services;

use App\Enums\DigitalSportsTech\DigitalSportsTechWnbaEnum;
use App\Models\WnbaGame;
use App\Models\WnbaGameMarket;
use App\Models\WnbaPlayer;
use App\Models\WnbaPlayerMarket;
use App\Models\WnbaTeam;
use App\Models\WnbaTeamMarket;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WnbaMarketService
{
    public function __construct(
        protected DigitalSportsTechService $digitalSportsTechService
    ){
    }

    private const GFM_GAMES_ENDPOINT = 'https://bv2-us.digitalsportstech.com/api/gfm/gamesByGfm';
    private const TEAM_MARKET_ENDPOINT = 'https://bv2-us.digitalsportstech.com/api/sgmMarkets/gfm/grouped';
    private const PLAYER_MARKET_ENDPOINT = 'https://bv2-us.digitalsportstech.com/api/dfm/marketsByOu';
    private const SPORTSBOOK_ALIAS = 'juancito';

    private const GAME_STATISTIC_MAP = [
        'handicap win' => 'handicap',
        'total points' => 'points',
        '1st half handicap win' => 'first_half_handicap',
        '1st half total points' => 'first_half_points',
    ];

    private const TEAM_STATISTIC_MAP = [
        'home team total points' => ['team' => 'home', 'field' => 'points'],
        'away team total points' => ['team' => 'away', 'field' => 'points'],
        'home team 1st half total points' => ['team' => 'home', 'field' => 'first_half_points'],
        'away team 1st half total points' => ['team' => 'away', 'field' => 'first_half_points'],
    ];

    public function getLiveMarkets($date = null): Collection
    {
        $tz = config('app.user_timezone', 'America/Santo_Domingo');

        $localDay = $date
            ? CarbonImmutable::parse($date, $tz)
            : CarbonImmutable::now($tz);

        // Ventana del dia local a UTC.
        $startUtc = $localDay->startOfDay()->setTimezone('UTC');
        $endUtc   = $startUtc->addDay();

        return WnbaGame::with(['homeTeam', 'awayTeam', 'market', 'teamMarkets', 'playerMarkets'])
            ->where('start_at', '>=', $startUtc)
            ->where('start_at', '<',  $endUtc)
            ->get();
    }

    public function getMatchups($date = null): Collection
    {
        $tz = config('app.user_timezone', 'America/Santo_Domingo');

        $localDay = $date
            ? CarbonImmutable::parse($date, $tz)
            : CarbonImmutable::now($tz);

        // Ventana del dia local a UTC.
        $startUtc = $localDay->startOfDay()->setTimezone('UTC');
        $endUtc   = $startUtc->addDay();

        // Pivote para separar proximos de pasados.
        // Si el dia es hoy, usamos ahora; de lo contrario, tratamos todo como proximo.
        $pivotUtc = $localDay->isSameDay(CarbonImmutable::now($tz))
            ? CarbonImmutable::now('UTC')
            : $startUtc;

        $playersWithStats = function ($playerQuery) {
            $playerQuery
                ->whereNotNull('market_id')
                ->with(['stats' => function ($q) {
                    $q->orderByDesc('wnba_player_stats.game_id')->limit(5);
                }]);
        };

        return WnbaGame::query()
            ->with([
                'awayTeam' => fn ($q) => $q->with(['players' => $playersWithStats]),
                'homeTeam' => fn ($q) => $q->with(['players' => $playersWithStats]),
            ])
            ->where('start_at', '>=', $startUtc)
            ->where('start_at', '<',  $endUtc)
            ->orderByRaw(
                // 1) Proximos primero (start_at >= pivot -> 0), luego pasados (1)
                // 2) Entre proximos: ASC (mas cercano primero)
                // 3) Entre pasados: DESC (el mas reciente primero)
                "CASE WHEN start_at >= ? THEN 0 ELSE 1 END,
                CASE WHEN start_at >= ? THEN start_at END ASC,
                CASE WHEN start_at <  ? THEN start_at END DESC",
                [$pivotUtc, $pivotUtc, $pivotUtc]
            )
            ->get();
    }

    public function syncMarkets(?string $marketId = null): void
    {
        $gamesPayload = $this->fetchGfmGames();

        if (empty($gamesPayload)) {
            Log::info('Sin datos de GFM para sincronizar mercados WNBA');
            return;
        }

        if ($marketId) {
            $gamesPayload = array_values(array_filter(
                $gamesPayload,
                fn (array $game) => $this->gameMatchesMarketId($game, $marketId)
            ));

            if (empty($gamesPayload)) {
                Log::warning('No se encontro juego WNBA para el market_id solicitado', [
                    'market_id' => $marketId,
                ]);
                return;
            }
        }

        $teams = WnbaTeam::all();
        $playersByMarketId = WnbaPlayer::whereNotNull('market_id')->get()->keyBy('market_id');

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
                Log::warning('No se pudo resolver equipos o fecha para juego WNBA', [
                    'market_id' => $resolvedMarketId,
                    'payload' => $gamePayload,
                ]);
                continue;
            }

            $game = $this->findMatchingGame($homeTeam->id, $awayTeam->id, $scheduledAt);

            if (!$game) {
                Log::warning('Juego WNBA no encontrado para sincronizacion de mercados', [
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
            $gameMarket = null;

            if ($teamPayload) {
                $gameMarket = $this->syncGameMarketData($game, $resolvedMarketId, $teamPayload);
                $this->syncTeamMarketData($game, $teamPayload, $gameMarket);
            } else {
                Log::info('Sin payload de mercados de equipo para juego WNBA', [
                    'market_id' => $resolvedMarketId,
                    'wnba_game_id' => $game->id,
                ]);
            }

            if ($playersByMarketId->isNotEmpty()) {
                $this->syncPlayerMarketData($game, $resolvedMarketId, $playersByMarketId);
            }
        }
    }

    public function syncPlayers()
    {
        return $this->digitalSportsTechService->syncWnbaPlayerMarketIds();
    }
    
    public function syncWnbaPlayers()
    {
        return $this->digitalSportsTechService->syncWnbaPlayerMarketIds();
    }

    private function fetchGfmGames(): array
    {
        try {
            $response = Http::timeout(20)->get(self::GFM_GAMES_ENDPOINT, [
                'sb' => self::SPORTSBOOK_ALIAS,
                'league' => 'wnba',
            ]);

            if (!$response->successful()) {
                Log::warning('Error HTTP al obtener juegos WNBA con mercados', [
                    'status' => $response->status(),
                ]);
                return [];
            }

            $payload = $response->json();

            return is_array($payload) ? $payload : [];
        } catch (\Throwable $exception) {
            Log::error('Excepcion al obtener juegos WNBA con mercados', [
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
            Log::warning('Fecha invalida en payload de juego WNBA', [
                'date' => $date,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /** @return array{0:?WnbaTeam,1:?WnbaTeam} */
    private function resolveTeams(Collection $teams, array $gamePayload): array
    {
        $homePayload = data_get($gamePayload, 'team1.0', []);
        $awayPayload = data_get($gamePayload, 'team2.0', []);

        $homeTeam = $this->findTeam($teams, $homePayload);
        $awayTeam = $this->findTeam($teams, $awayPayload);

        return [$homeTeam, $awayTeam];
    }

    private function findTeam(Collection $teams, array $teamPayload): ?WnbaTeam
    {
        $abbreviation = strtoupper((string) ($teamPayload['abbreviation'] ?? ''));

        if ($abbreviation !== '') {
            $team = $teams->first(function (WnbaTeam $team) use ($abbreviation) {
                return strtoupper($team->short_name) === $abbreviation;
            });

            if ($team) {
                return $team;
            }
        }

        $title = $teamPayload['title'] ?? null;

        if ($title) {
            $normalizedTitle = $this->normalizeName($title);

            $team = $teams->first(function (WnbaTeam $team) use ($normalizedTitle) {
                $composed = $this->normalizeName($team->city . ' ' . $team->name);
                return $composed === $normalizedTitle;
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

    private function findMatchingGame(int $homeTeamId, int $awayTeamId, Carbon $scheduledAt): ?WnbaGame
    {
        $windowStart = $scheduledAt->copy()->subHours(6);
        $windowEnd = $scheduledAt->copy()->addHours(6);

        return WnbaGame::where('home_team_id', $homeTeamId)
            ->where('away_team_id', $awayTeamId)
            ->whereBetween('start_at', [$windowStart, $windowEnd])
            ->orderByRaw('ABS(TIMESTAMPDIFF(SECOND, start_at, ?))', [$scheduledAt])
            ->first();
    }

    private function fetchTeamMarketPayload(string $marketId): ?array
    {
        try {
            $response = Http::timeout(15)->get(self::TEAM_MARKET_ENDPOINT, [
                'sb' => self::SPORTSBOOK_ALIAS,
                'gameId' => $marketId,
            ]);

            if (!$response->successful()) {
                Log::warning('Error HTTP al obtener mercados de equipos WNBA', [
                    'game_id' => $marketId,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $payload = $response->json();

            return is_array($payload) ? $payload : null;
        } catch (\Throwable $exception) {
            Log::error('Excepcion al obtener mercados de equipos WNBA', [
                'game_id' => $marketId,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function syncGameMarketData(WnbaGame $game, string $marketId, array $teamPayload): ?WnbaGameMarket
    {
        $toWin = collect($teamPayload)->first(function ($entry) {
            return isset($entry['statistic']) && strtolower($entry['statistic']) === 'to win';
        });

        if (empty($toWin['markets'])) {
            Log::info('Payload de equipos WNBA sin "to win"', [
                'game_id' => $marketId,
                'wnba_game_id' => $game->id,
            ]);

            return WnbaGameMarket::where('game_id', $game->id)->first();
        }

        $favoriteTeamId = $this->determineFavoriteTeamId(
            $toWin['markets'],
            $game->home_team_id,
            $game->away_team_id
        );

        $candidateValues = ['favorite_team_id' => $favoriteTeamId];

        foreach (self::GAME_STATISTIC_MAP as $statistic => $field) {
            $value = $this->extractPrincipalValue($teamPayload, $statistic);

            if (in_array($field, ['handicap', 'first_half_handicap'], true)) {
                $value = $this->normalizeHandicapValue($value);
            }

            if ($value !== null && $value !== '') {
                $candidateValues[$field] = (string) $value;
            }
        }

        $updates = array_filter(
            $candidateValues,
            static fn ($value) => $value !== null
        );

        if (empty($updates)) {
            return WnbaGameMarket::where('game_id', $game->id)->first();
        }

        $market = WnbaGameMarket::firstOrNew(['game_id' => $game->id]);
        $updates['handicap'] = $updates['handicap'] ?? 0;
        $market->fill($updates);
        $market->save();

        return $market->fresh();
    }

    private function syncTeamMarketData(WnbaGame $game, array $teamPayload, ?WnbaGameMarket $gameMarket): void
    {
        $homeUpdates = [];
        $awayUpdates = [];

        foreach (self::TEAM_STATISTIC_MAP as $statistic => $config) {
            $value = $this->extractPrincipalValue($teamPayload, $statistic);

            if ($value === null || $value === '') {
                continue;
            }

            $bucket = $config['team'] === 'home' ? 'homeUpdates' : 'awayUpdates';
            ${$bucket}[$config['field']] = (string) $value;
        }

        $this->distributeFirstHalfPoints($game, $gameMarket, $homeUpdates, $awayUpdates);

        if (!empty($homeUpdates)) {
            $homeMarket = WnbaTeamMarket::firstOrNew([
                'game_id' => $game->id,
                'team_id' => $game->home_team_id,
            ]);
            $homeMarket->fill($homeUpdates);
            $homeMarket->save();
        }

        if (!empty($awayUpdates)) {
            $awayMarket = WnbaTeamMarket::firstOrNew([
                'game_id' => $game->id,
                'team_id' => $game->away_team_id,
            ]);
            $awayMarket->fill($awayUpdates);
            $awayMarket->save();
        }
    }

    private function distributeFirstHalfPoints(
        WnbaGame $game,
        ?WnbaGameMarket $gameMarket,
        array &$homeUpdates,
        array &$awayUpdates
    ): void {
        if (!$gameMarket) {
            return;
        }

        if (array_key_exists('first_half_points', $homeUpdates) && array_key_exists('first_half_points', $awayUpdates)) {
            return;
        }

        $total = $gameMarket->first_half_points;
        $handicap = $gameMarket->first_half_handicap;
        $favoriteTeamId = $gameMarket->favorite_team_id;

        if ($total === null || $handicap === null || $favoriteTeamId === null) {
            return;
        }

        $total = (float) $total;
        $handicap = (float) $handicap;
        $base = $total / 2.0;
        $adjustment = $handicap / 2.0;

        if ($favoriteTeamId === $game->home_team_id) {
            $home = $base + $adjustment;
            $away = $total - $home;
        } elseif ($favoriteTeamId === $game->away_team_id) {
            $away = $base + $adjustment;
            $home = $total - $away;
        } else {
            return;
        }

        $homeValue = number_format($home, 1, '.', '');
        $awayValue = number_format($away, 1, '.', '');

        if (!array_key_exists('first_half_points', $homeUpdates)) {
            $homeUpdates['first_half_points'] = $homeValue;
        }

        if (!array_key_exists('first_half_points', $awayUpdates)) {
            $awayUpdates['first_half_points'] = $awayValue;
        }
    }

    private function syncPlayerMarketData(WnbaGame $game, string $marketId, Collection $playersByMarketId): void
    {
        $statBlocks = $this->fetchPlayerStatPayloads($marketId);

        if (empty($statBlocks)) {
            Log::info('Sin mercados de jugadores WNBA disponibles', [
                'game_id' => $marketId,
                'wnba_game_id' => $game->id,
            ]);
            return;
        }

        $playerRows = [];

        foreach ($statBlocks as $block) {
            foreach ($block['payload'] as $entry) {
                $players = $entry['players'] ?? [];

                foreach ($players as $playerBlock) {
                    $playerMarketId = $playerBlock['id'] ?? null;

                    if (!$playerMarketId) {
                        continue;
                    }

                    $player = $playersByMarketId->get($playerMarketId);

                    if (!$player) {
                        continue;
                    }

                    $value = $this->selectBestPlayerValue($playerBlock['markets'] ?? []);

                    if ($value === null) {
                        continue;
                    }

                    if (!isset($playerRows[$player->id])) {
                        $playerRows[$player->id] = [
                            'game_id' => $game->id,
                            'player_id' => $player->id,
                        ];
                    }

                    $playerRows[$player->id][$block['column']] = (string) $value;
                }
            }
        }

        if (empty($playerRows)) {
            return;
        }

        foreach ($playerRows as $row) {
            $attributes = [
                'game_id' => $row['game_id'],
                'player_id' => $row['player_id'],
            ];

            $updates = [];

            foreach (DigitalSportsTechWnbaEnum::all() as $column => $_) {
                if (array_key_exists($column, $row)) {
                    $updates[$column] = $row[$column];
                }
            }

            if (!empty($updates)) {
                $playerMarket = WnbaPlayerMarket::firstOrNew($attributes);
                $playerMarket->fill($updates);
                $playerMarket->save();
            }
        }
    }

    private function fetchPlayerStatPayloads(string $marketId): array
    {
        $statBlocks = [];

        foreach (DigitalSportsTechWnbaEnum::all() as $column => $statistic) {
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
                Log::info('Mercado de jugadores WNBA no disponible', [
                    'game_id' => $marketId,
                    'statistic' => $statistic,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $payload = $response->json();

            return is_array($payload) ? $payload : null;
        } catch (\Throwable $exception) {
            Log::error('Excepcion al obtener mercado de jugadores WNBA', [
                'game_id' => $marketId,
                'statistic' => $statistic,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
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

        return match ($type) {
            '1' => $homeTeamId,
            '2' => $awayTeamId,
            default => null,
        };
    }

    private function extractPrincipalValue(array $payload, string $statistic): ?string
    {
        $statisticData = collect($payload)->first(function ($entry) use ($statistic) {
            $entryStat = $entry['statistic'] ?? null;
            return $entryStat && strtolower($entryStat) === strtolower($statistic);
        });

        if (empty($statisticData['markets']) || !is_array($statisticData['markets'])) {
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

        foreach ($groups as $value => $entries) {
            if ($value === '' || $value === null || $entries->count() < 2) {
                continue;
            }

            $odds = $entries->pluck('odds')->filter()->map(static fn ($odd) => (float) $odd);

            if ($odds->count() < 2) {
                continue;
            }

            $diff = $odds->max() - $odds->min();

            if ($smallestDiff === null || $diff < $smallestDiff) {
                $smallestDiff = $diff;
                $bestGroup = $value;
            }
        }

        return $bestGroup !== null ? (string) $bestGroup : null;
    }

    private function normalizeHandicapValue(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $numeric = (float) $value;

        return number_format(abs($numeric), 1, '.', '');
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

        $bestValue = null;
        $smallestDiff = null;

        foreach ($groups as $value => $entries) {
            if ($entries->count() < 2) {
                continue;
            }

            $odds = $entries->pluck('odds')->filter()->map(static fn ($odd) => (float) $odd);

            if ($odds->count() < 2) {
                continue;
            }

            $diff = $odds->max() - $odds->min();

            if ($smallestDiff === null || $diff < $smallestDiff) {
                $smallestDiff = $diff;
                $bestValue = $value;
            }
        }

        return $bestValue !== null ? (string) $bestValue : null;
    }

}
