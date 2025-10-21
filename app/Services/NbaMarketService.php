<?php

namespace App\Services;

use App\Enums\DigitalSportsTech\DigitalSportsTechNbaEnum;
use App\Models\NbaGame;
use App\Models\NbaGameMarket;
use App\Models\NbaPlayer;
use App\Models\NbaPlayerMarket;
use App\Models\NbaTeam;
use App\Models\NbaTeamMarket;
use App\Repositories\NbaTeamRepository;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NbaMarketService
{
    public function __construct(
        protected DigitalSportsTechService $digitalSportsTechService,
        protected NbaTeamRepository $nbaTeamRepository
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

        // Ventana del día local -> UTC
        $startUtc = $localDay->startOfDay()->setTimezone('UTC');
        $endUtc   = $startUtc->addDay();

        return NbaGame::with(['homeTeam', 'awayTeam', 'market', 'teamMarkets', 'playerMarkets'])
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

        // Ventana del día local -> UTC
        $startUtc = $localDay->startOfDay()->setTimezone('UTC');
        $endUtc   = $startUtc->addDay();

        // Pivote para separar “próximos” de “pasados”
        // Si el día es hoy, usamos "ahora"; de lo contrario, tratamos todo como "próximo"
        $pivotUtc = $localDay->isSameDay(CarbonImmutable::now($tz))
            ? CarbonImmutable::now('UTC')
            : $startUtc;

        $playersWithStats = function ($playerQuery) {
            $playerQuery
                ->whereNotNull('market_id')
                ->with(['stats' => function ($q) {
                    $q->orderByDesc('nba_player_stats.game_id')->limit(5);
                }]);
        };

        return NbaGame::query()
            ->with([
                'awayTeam' => fn ($q) => $q->with(['players' => $playersWithStats]),
                'homeTeam' => fn ($q) => $q->with(['players' => $playersWithStats]),
            ])
            ->where('start_at', '>=', $startUtc)
            ->where('start_at', '<',  $endUtc)
            ->orderByRaw(
                // 1) Próximos primero (start_at >= pivot -> 0), luego pasados (1)
                // 2) Entre próximos: ASC (más cercano primero)
                // 3) Entre pasados: DESC (el más reciente primero)
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
            Log::info('Sin datos de GFM para sincronizar mercados NBA');
            return;
        }

        if ($marketId) {
            $gamesPayload = array_values(array_filter(
                $gamesPayload,
                fn (array $game) => $this->gameMatchesMarketId($game, $marketId)
            ));

            if (empty($gamesPayload)) {
                Log::warning('No se encontró juego NBA para el market_id solicitado', [
                    'market_id' => $marketId,
                ]);
                return;
            }
        }

        $teams = NbaTeam::all();
        $playersByMarketId = NbaPlayer::whereNotNull('market_id')->get()->keyBy('market_id');

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
                Log::warning('No se pudo resolver equipos o fecha para juego NBA', [
                    'market_id' => $resolvedMarketId,
                    'payload' => $gamePayload,
                ]);
                continue;
            }

            $game = $this->findMatchingGame($homeTeam->id, $awayTeam->id, $scheduledAt);

            if (!$game) {
                Log::warning('Juego NBA no encontrado para sincronización de mercados', [
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
                Log::info('Sin payload de mercados de equipo para juego NBA', [
                    'market_id' => $resolvedMarketId,
                    'nba_game_id' => $game->id,
                ]);
            }

            if ($playersByMarketId->isNotEmpty()) {
                $this->syncPlayerMarketData($game, $resolvedMarketId, $playersByMarketId);
            }
        }
    }

    public function syncPlayers()
    {
        return $this->digitalSportsTechService->syncNbaPlayerMarketIds();
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
                'league' => 'nba',
            ]);

            if (!$response->successful()) {
                Log::warning('Error HTTP al obtener juegos NBA con mercados', [
                    'status' => $response->status(),
                ]);
                return [];
            }

            $payload = $response->json();

            return is_array($payload) ? $payload : [];
        } catch (\Throwable $exception) {
            Log::error('Excepción al obtener juegos NBA con mercados', [
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
            Log::warning('Fecha inválida en payload de juego NBA', [
                'date' => $date,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /** @return array{0:?NbaTeam,1:?NbaTeam} */
    private function resolveTeams(Collection $teams, array $gamePayload): array
    {
        $homePayload = data_get($gamePayload, 'team1.0', []);
        $awayPayload = data_get($gamePayload, 'team2.0', []);

        $homeTeam = $this->findTeam($teams, $homePayload);
        $awayTeam = $this->findTeam($teams, $awayPayload);

        return [$homeTeam, $awayTeam];
    }

    private function findTeam(Collection $teams, array $teamPayload): ?NbaTeam
    {
        $abbreviation = strtoupper((string) ($teamPayload['abbreviation'] ?? ''));

        if ($abbreviation !== '') {
            $team = $teams->first(function (NbaTeam $team) use ($abbreviation) {
                return strtoupper($team->short_name) === $abbreviation;
            });

            if ($team) {
                return $team;
            }
        }

        $title = $teamPayload['title'] ?? null;

        if ($title) {
            $normalizedTitle = $this->normalizeName($title);

            $team = $teams->first(function (NbaTeam $team) use ($normalizedTitle) {
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

    private function findMatchingGame(int $homeTeamId, int $awayTeamId, Carbon $scheduledAt): ?NbaGame
    {
        $windowStart = $scheduledAt->copy()->subHours(6);
        $windowEnd = $scheduledAt->copy()->addHours(6);

        return NbaGame::where('home_team_id', $homeTeamId)
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
                Log::warning('Error HTTP al obtener mercados de equipos NBA', [
                    'game_id' => $marketId,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $payload = $response->json();

            return is_array($payload) ? $payload : null;
        } catch (\Throwable $exception) {
            Log::error('Excepción al obtener mercados de equipos NBA', [
                'game_id' => $marketId,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function syncGameMarketData(NbaGame $game, string $marketId, array $teamPayload): ?NbaGameMarket
    {
        $toWin = collect($teamPayload)->first(function ($entry) {
            return isset($entry['statistic']) && strtolower($entry['statistic']) === 'to win';
        });

        if (empty($toWin['markets'])) {
            Log::info('Payload de equipos NBA sin "to win"', [
                'game_id' => $marketId,
                'nba_game_id' => $game->id,
            ]);

            return NbaGameMarket::where('game_id', $game->id)->first();
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
            return NbaGameMarket::where('game_id', $game->id)->first();
        }

        $market = NbaGameMarket::firstOrNew(['game_id' => $game->id]);
        $market->fill($updates);
        $market->save();

        return $market->fresh();
    }

    private function syncTeamMarketData(NbaGame $game, array $teamPayload, ?NbaGameMarket $gameMarket): void
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
            $homeMarket = NbaTeamMarket::firstOrNew([
                'game_id' => $game->id,
                'team_id' => $game->home_team_id,
            ]);
            $homeMarket->fill($homeUpdates);
            $homeMarket->save();
        }

        if (!empty($awayUpdates)) {
            $awayMarket = NbaTeamMarket::firstOrNew([
                'game_id' => $game->id,
                'team_id' => $game->away_team_id,
            ]);
            $awayMarket->fill($awayUpdates);
            $awayMarket->save();
        }
    }

    private function distributeFirstHalfPoints(
        NbaGame $game,
        ?NbaGameMarket $gameMarket,
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

    private function syncPlayerMarketData(NbaGame $game, string $marketId, Collection $playersByMarketId): void
    {
        $statBlocks = $this->fetchPlayerStatPayloads($marketId);

        if (empty($statBlocks)) {
            Log::info('Sin mercados de jugadores NBA disponibles', [
                'game_id' => $marketId,
                'nba_game_id' => $game->id,
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

            foreach (DigitalSportsTechNbaEnum::all() as $column => $_) {
                if (array_key_exists($column, $row)) {
                    $updates[$column] = $row[$column];
                }
            }

            if (!empty($updates)) {
                $playerMarket = NbaPlayerMarket::firstOrNew($attributes);
                $playerMarket->fill($updates);
                $playerMarket->save();
            }
        }
    }

    private function fetchPlayerStatPayloads(string $marketId): array
    {
        $statBlocks = [];

        foreach (DigitalSportsTechNbaEnum::all() as $column => $statistic) {
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
                Log::info('Mercado de jugadores NBA no disponible', [
                    'game_id' => $marketId,
                    'statistic' => $statistic,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $payload = $response->json();

            return is_array($payload) ? $payload : null;
        } catch (\Throwable $exception) {
            Log::error('Excepción al obtener mercado de jugadores NBA', [
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

    public function getLineups()
    {
        return json_decode('
            {
                "games": [
                    {
                        "gameId": "0022401128",
                        "gameStatus": 1,
                        "gameStatusText": "3:00 pm ET",
                        "homeTeam": {
                            "teamId": 1610612737,
                            "teamAbbreviation": "ATL",
                            "players": [
                                {
                                    "personId": 1630700,
                                    "teamId": 1610612737,
                                    "firstName": "Dyson",
                                    "lastName": "Daniels",
                                    "playerName": "Dyson Daniels",
                                    "lineupStatus": "Confirmed",
                                    "position": "SG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1642258,
                                    "teamId": 1610612737,
                                    "firstName": "Zaccharie",
                                    "lastName": "Risacher",
                                    "playerName": "Zaccharie Risacher",
                                    "lineupStatus": "Confirmed",
                                    "position": "SF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1629027,
                                    "teamId": 1610612737,
                                    "firstName": "Trae",
                                    "lastName": "Young",
                                    "playerName": "Trae Young",
                                    "lineupStatus": "Confirmed",
                                    "position": "PG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1631243,
                                    "teamId": 1610612737,
                                    "firstName": "Mouhamed",
                                    "lastName": "Gueye",
                                    "playerName": "Mouhamed Gueye",
                                    "lineupStatus": "Confirmed",
                                    "position": "PF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1630168,
                                    "teamId": 1610612737,
                                    "firstName": "Onyeka",
                                    "lastName": "Okongwu",
                                    "playerName": "Onyeka Okongwu",
                                    "lineupStatus": "Confirmed",
                                    "position": "C",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1627747,
                                    "teamId": 1610612737,
                                    "firstName": "Caris",
                                    "lastName": "LeVert",
                                    "playerName": "Caris LeVert",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1627777,
                                    "teamId": 1610612737,
                                    "firstName": "Georges",
                                    "lastName": "Niang",
                                    "playerName": "Georges Niang",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1629611,
                                    "teamId": 1610612737,
                                    "firstName": "Terance",
                                    "lastName": "Mann",
                                    "playerName": "Terance Mann",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1629726,
                                    "teamId": 1610612737,
                                    "firstName": "Garrison",
                                    "lastName": "Mathews",
                                    "playerName": "Garrison Mathews",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1630249,
                                    "teamId": 1610612737,
                                    "firstName": "Vít",
                                    "lastName": "Krejčí",
                                    "playerName": "Vít Krejčí",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1630811,
                                    "teamId": 1610612737,
                                    "firstName": "Keaton",
                                    "lastName": "Wallace",
                                    "playerName": "Keaton Wallace",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1631230,
                                    "teamId": 1610612737,
                                    "firstName": "Dominick",
                                    "lastName": "Barlow",
                                    "playerName": "Dominick Barlow",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1631342,
                                    "teamId": 1610612737,
                                    "firstName": "Daeqwon",
                                    "lastName": "Plowden",
                                    "playerName": "Daeqwon Plowden",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 203991,
                                    "teamId": 1610612737,
                                    "firstName": "Clint",
                                    "lastName": "Capela",
                                    "playerName": "Clint Capela",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Inactive",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1626204,
                                    "teamId": 1610612737,
                                    "firstName": "Larry",
                                    "lastName": "Nance Jr.",
                                    "playerName": "Larry Nance Jr.",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Inactive",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1630552,
                                    "teamId": 1610612737,
                                    "firstName": "Jalen",
                                    "lastName": "Johnson",
                                    "playerName": "Jalen Johnson",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Inactive",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1631210,
                                    "teamId": 1610612737,
                                    "firstName": "Jacob",
                                    "lastName": "Toppin",
                                    "playerName": "Jacob Toppin",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Inactive",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1641723,
                                    "teamId": 1610612737,
                                    "firstName": "Kobe",
                                    "lastName": "Bufkin",
                                    "playerName": "Kobe Bufkin",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Inactive",
                                    "timestamp": "2025-04-05T14:34:00"
                                }
                            ]
                        },
                        "awayTeam": {
                            "teamId": 1610612752,
                            "teamAbbreviation": "NYK",
                            "players": [
                                {
                                    "personId": 1628969,
                                    "teamId": 1610612752,
                                    "firstName": "Mikal",
                                    "lastName": "Bridges",
                                    "playerName": "Mikal Bridges",
                                    "lineupStatus": "Confirmed",
                                    "position": "SG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1628404,
                                    "teamId": 1610612752,
                                    "firstName": "Josh",
                                    "lastName": "Hart",
                                    "playerName": "Josh Hart",
                                    "lineupStatus": "Confirmed",
                                    "position": "SF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1626153,
                                    "teamId": 1610612752,
                                    "firstName": "Delon",
                                    "lastName": "Wright",
                                    "playerName": "Delon Wright",
                                    "lineupStatus": "Confirmed",
                                    "position": "PG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1628384,
                                    "teamId": 1610612752,
                                    "firstName": "OG",
                                    "lastName": "Anunoby",
                                    "playerName": "OG Anunoby",
                                    "lineupStatus": "Confirmed",
                                    "position": "PF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1626157,
                                    "teamId": 1610612752,
                                    "firstName": "Karl-Anthony",
                                    "lastName": "Towns",
                                    "playerName": "Karl-Anthony Towns",
                                    "lineupStatus": "Confirmed",
                                    "position": "C",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 200782,
                                    "teamId": 1610612752,
                                    "firstName": "P.J.",
                                    "lastName": "Tucker",
                                    "playerName": "P.J. Tucker",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1626166,
                                    "teamId": 1610612752,
                                    "firstName": "Cameron",
                                    "lastName": "Payne",
                                    "playerName": "Cameron Payne",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1628973,
                                    "teamId": 1610612752,
                                    "firstName": "Jalen",
                                    "lastName": "Brunson",
                                    "playerName": "Jalen Brunson",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1629013,
                                    "teamId": 1610612752,
                                    "firstName": "Landry",
                                    "lastName": "Shamet",
                                    "playerName": "Landry Shamet",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1630173,
                                    "teamId": 1610612752,
                                    "firstName": "Precious",
                                    "lastName": "Achiuwa",
                                    "playerName": "Precious Achiuwa",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1630699,
                                    "teamId": 1610612752,
                                    "firstName": "MarJon",
                                    "lastName": "Beauchamp",
                                    "playerName": "MarJon Beauchamp",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1641755,
                                    "teamId": 1610612752,
                                    "firstName": "Kevin",
                                    "lastName": "McCullar Jr.",
                                    "playerName": "Kevin McCullar Jr.",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1641817,
                                    "teamId": 1610612752,
                                    "firstName": "Anton",
                                    "lastName": "Watson",
                                    "playerName": "Anton Watson",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1642278,
                                    "teamId": 1610612752,
                                    "firstName": "Tyler",
                                    "lastName": "Kolek",
                                    "playerName": "Tyler Kolek",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1642359,
                                    "teamId": 1610612752,
                                    "firstName": "Pacôme",
                                    "lastName": "Dadiet",
                                    "playerName": "Pacôme Dadiet",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1629011,
                                    "teamId": 1610612752,
                                    "firstName": "Mitchell",
                                    "lastName": "Robinson",
                                    "playerName": "Mitchell Robinson",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Inactive",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1630540,
                                    "teamId": 1610612752,
                                    "firstName": "Miles",
                                    "lastName": "McBride",
                                    "playerName": "Miles McBride",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Inactive",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1630574,
                                    "teamId": 1610612752,
                                    "firstName": "Ariel",
                                    "lastName": "Hukporti",
                                    "playerName": "Ariel Hukporti",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Inactive",
                                    "timestamp": "2025-04-05T14:34:00"
                                }
                            ]
                        }
                    },
                    {
                        "gameId": "0022401129",
                        "gameStatus": 1,
                        "gameStatusText": "7:00 pm ET",
                        "homeTeam": {
                            "teamId": 1610612765,
                            "teamAbbreviation": "DET",
                            "players": [
                                {
                                    "personId": 203501,
                                    "teamId": 1610612765,
                                    "firstName": "Tim",
                                    "lastName": "Hardaway Jr.",
                                    "playerName": "Tim Hardaway Jr.",
                                    "lineupStatus": "Expected",
                                    "position": "SG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1641709,
                                    "teamId": 1610612765,
                                    "firstName": "Ausar",
                                    "lastName": "Thompson",
                                    "playerName": "Ausar Thompson",
                                    "lineupStatus": "Expected",
                                    "position": "SF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1630595,
                                    "teamId": 1610612765,
                                    "firstName": "Cade",
                                    "lastName": "Cunningham",
                                    "playerName": "Cade Cunningham",
                                    "lineupStatus": "Expected",
                                    "position": "PG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 202699,
                                    "teamId": 1610612765,
                                    "firstName": "Tobias",
                                    "lastName": "Harris",
                                    "playerName": "Tobias Harris",
                                    "lineupStatus": "Expected",
                                    "position": "PF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1631105,
                                    "teamId": 1610612765,
                                    "firstName": "Jalen",
                                    "lastName": "Duren",
                                    "playerName": "Jalen Duren",
                                    "lineupStatus": "Expected",
                                    "position": "C",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                }
                            ]
                        },
                        "awayTeam": {
                            "teamId": 1610612763,
                            "teamAbbreviation": "MEM",
                            "players": [
                                {
                                    "personId": 1630217,
                                    "teamId": 1610612763,
                                    "firstName": "Desmond",
                                    "lastName": "Bane",
                                    "playerName": "Desmond Bane",
                                    "lineupStatus": "Expected",
                                    "position": "SG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1642377,
                                    "teamId": 1610612763,
                                    "firstName": "Jaylen",
                                    "lastName": "Wells",
                                    "playerName": "Jaylen Wells",
                                    "lineupStatus": "Expected",
                                    "position": "SF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1629630,
                                    "teamId": 1610612763,
                                    "firstName": "Ja",
                                    "lastName": "Morant",
                                    "playerName": "Ja Morant",
                                    "lineupStatus": "Expected",
                                    "position": "PG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1628991,
                                    "teamId": 1610612763,
                                    "firstName": "Jaren",
                                    "lastName": "Jackson Jr.",
                                    "playerName": "Jaren Jackson Jr.",
                                    "lineupStatus": "Expected",
                                    "position": "PF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1641744,
                                    "teamId": 1610612763,
                                    "firstName": "Zach",
                                    "lastName": "Edey",
                                    "playerName": "Zach Edey",
                                    "lineupStatus": "Expected",
                                    "position": "C",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                }
                            ]
                        }
                    },
                    {
                        "gameId": "0022401130",
                        "gameStatus": 1,
                        "gameStatusText": "7:00 pm ET",
                        "homeTeam": {
                            "teamId": 1610612755,
                            "teamAbbreviation": "PHI",
                            "players": [
                                {
                                    "personId": 1629656,
                                    "teamId": 1610612755,
                                    "firstName": "Quentin",
                                    "lastName": "Grimes",
                                    "playerName": "Quentin Grimes",
                                    "lineupStatus": "Expected",
                                    "position": "SG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1642348,
                                    "teamId": 1610612755,
                                    "firstName": "Justin",
                                    "lastName": "Edwards",
                                    "playerName": "Justin Edwards",
                                    "lineupStatus": "Expected",
                                    "position": "SF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1630215,
                                    "teamId": 1610612755,
                                    "firstName": "Jared",
                                    "lastName": "Butler",
                                    "playerName": "Jared Butler",
                                    "lineupStatus": "Expected",
                                    "position": "PG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1627824,
                                    "teamId": 1610612755,
                                    "firstName": "Guerschon",
                                    "lastName": "Yabusele",
                                    "playerName": "Guerschon Yabusele",
                                    "lineupStatus": "Expected",
                                    "position": "PF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1641737,
                                    "teamId": 1610612755,
                                    "firstName": "Adem",
                                    "lastName": "Bona",
                                    "playerName": "Adem Bona",
                                    "lineupStatus": "Expected",
                                    "position": "C",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                }
                            ]
                        },
                        "awayTeam": {
                            "teamId": 1610612750,
                            "teamAbbreviation": "MIN",
                            "players": [
                                {
                                    "personId": 1630162,
                                    "teamId": 1610612750,
                                    "firstName": "Anthony",
                                    "lastName": "Edwards",
                                    "playerName": "Anthony Edwards",
                                    "lineupStatus": "Expected",
                                    "position": "SG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1630183,
                                    "teamId": 1610612750,
                                    "firstName": "Jaden",
                                    "lastName": "McDaniels",
                                    "playerName": "Jaden McDaniels",
                                    "lineupStatus": "Expected",
                                    "position": "SF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 201144,
                                    "teamId": 1610612750,
                                    "firstName": "Mike",
                                    "lastName": "Conley",
                                    "playerName": "Mike Conley",
                                    "lineupStatus": "Expected",
                                    "position": "PG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 203944,
                                    "teamId": 1610612750,
                                    "firstName": "Julius",
                                    "lastName": "Randle",
                                    "playerName": "Julius Randle",
                                    "lineupStatus": "Expected",
                                    "position": "PF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 203497,
                                    "teamId": 1610612750,
                                    "firstName": "Rudy",
                                    "lastName": "Gobert",
                                    "playerName": "Rudy Gobert",
                                    "lineupStatus": "Expected",
                                    "position": "C",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                }
                            ]
                        }
                    },
                    {
                        "gameId": "0022401131",
                        "gameStatus": 1,
                        "gameStatusText": "8:00 pm ET",
                        "homeTeam": {
                            "teamId": 1610612748,
                            "teamAbbreviation": "MIA",
                            "players": [
                                {
                                    "personId": 202692,
                                    "teamId": 1610612748,
                                    "firstName": "Alec",
                                    "lastName": "Burks",
                                    "playerName": "Alec Burks",
                                    "lineupStatus": "Expected",
                                    "position": "SG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1641796,
                                    "teamId": 1610612748,
                                    "firstName": "Pelle",
                                    "lastName": "Larsson",
                                    "playerName": "Pelle Larsson",
                                    "lineupStatus": "Expected",
                                    "position": "SF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1629639,
                                    "teamId": 1610612748,
                                    "firstName": "Tyler",
                                    "lastName": "Herro",
                                    "playerName": "Tyler Herro",
                                    "lineupStatus": "Expected",
                                    "position": "PG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1628389,
                                    "teamId": 1610612748,
                                    "firstName": "Bam",
                                    "lastName": "Adebayo",
                                    "playerName": "Bam Adebayo",
                                    "lineupStatus": "Expected",
                                    "position": "PF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1642276,
                                    "teamId": 1610612748,
                                    "firstName": "Kel\'el",
                                    "lastName": "Ware",
                                    "playerName": "Kel\'el Ware",
                                    "lineupStatus": "Expected",
                                    "position": "C",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                }
                            ]
                        },
                        "awayTeam": {
                            "teamId": 1610612749,
                            "teamAbbreviation": "MIL",
                            "players": [
                                {
                                    "personId": 1627752,
                                    "teamId": 1610612749,
                                    "firstName": "Taurean",
                                    "lastName": "Prince",
                                    "playerName": "Taurean Prince",
                                    "lineupStatus": "Expected",
                                    "position": "SG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1628398,
                                    "teamId": 1610612749,
                                    "firstName": "Kyle",
                                    "lastName": "Kuzma",
                                    "playerName": "Kyle Kuzma",
                                    "lineupStatus": "Expected",
                                    "position": "SF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1631157,
                                    "teamId": 1610612749,
                                    "firstName": "Ryan",
                                    "lastName": "Rollins",
                                    "playerName": "Ryan Rollins",
                                    "lineupStatus": "Expected",
                                    "position": "PG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 203507,
                                    "teamId": 1610612749,
                                    "firstName": "Giannis",
                                    "lastName": "Antetokounmpo",
                                    "playerName": "Giannis Antetokounmpo",
                                    "lineupStatus": "Expected",
                                    "position": "PF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 201572,
                                    "teamId": 1610612749,
                                    "firstName": "Brook",
                                    "lastName": "Lopez",
                                    "playerName": "Brook Lopez",
                                    "lineupStatus": "Expected",
                                    "position": "C",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                }
                            ]
                        }
                    },
                    {
                        "gameId": "0022401132",
                        "gameStatus": 1,
                        "gameStatusText": "10:30 pm ET",
                        "homeTeam": {
                            "teamId": 1610612746,
                            "teamAbbreviation": "LAC",
                            "players": [
                                {
                                    "personId": 1627739,
                                    "teamId": 1610612746,
                                    "firstName": "Kris",
                                    "lastName": "Dunn",
                                    "playerName": "Kris Dunn",
                                    "lineupStatus": "Expected",
                                    "position": "SG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1626181,
                                    "teamId": 1610612746,
                                    "firstName": "Norman",
                                    "lastName": "Powell",
                                    "playerName": "Norman Powell",
                                    "lineupStatus": "Expected",
                                    "position": "SF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 201935,
                                    "teamId": 1610612746,
                                    "firstName": "James",
                                    "lastName": "Harden",
                                    "playerName": "James Harden",
                                    "lineupStatus": "Expected",
                                    "position": "PG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1627884,
                                    "teamId": 1610612746,
                                    "firstName": "Derrick",
                                    "lastName": "Jones Jr.",
                                    "playerName": "Derrick Jones Jr.",
                                    "lineupStatus": "Expected",
                                    "position": "PF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1627826,
                                    "teamId": 1610612746,
                                    "firstName": "Ivica",
                                    "lastName": "Zubac",
                                    "playerName": "Ivica Zubac",
                                    "lineupStatus": "Expected",
                                    "position": "C",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                }
                            ]
                        },
                        "awayTeam": {
                            "teamId": 1610612742,
                            "teamAbbreviation": "DAL",
                            "players": [
                                {
                                    "personId": 202691,
                                    "teamId": 1610612742,
                                    "firstName": "Klay",
                                    "lastName": "Thompson",
                                    "playerName": "Klay Thompson",
                                    "lineupStatus": "Expected",
                                    "position": "SG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1629023,
                                    "teamId": 1610612742,
                                    "firstName": "P.J.",
                                    "lastName": "Washington",
                                    "playerName": "P.J. Washington",
                                    "lineupStatus": "Expected",
                                    "position": "SF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 203915,
                                    "teamId": 1610612742,
                                    "firstName": "Spencer",
                                    "lastName": "Dinwiddie",
                                    "playerName": "Spencer Dinwiddie",
                                    "lineupStatus": "Expected",
                                    "position": "PG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 203076,
                                    "teamId": 1610612742,
                                    "firstName": "Anthony",
                                    "lastName": "Davis",
                                    "playerName": "Anthony Davis",
                                    "lineupStatus": "Expected",
                                    "position": "PF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1641726,
                                    "teamId": 1610612742,
                                    "firstName": "Dereck",
                                    "lastName": "Lively II",
                                    "playerName": "Dereck Lively II",
                                    "lineupStatus": "Expected",
                                    "position": "C",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                }
                            ]
                        }
                    }
                ]
            }
        ');
    }
}
