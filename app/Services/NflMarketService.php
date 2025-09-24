<?php

namespace App\Services;

use App\Models\NflGame;
use App\Models\NflGameMarket;
use App\Models\NflPlayer;
use App\Models\NflPlayerMarket;
use App\Models\NflTeam;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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

    public function __construct(
    ) {
    }

    public function getMatchups()
    {
        // $cacheKey = now()->toDateString();

        // $matchups = false; 
        // // $matchups = Cache::tags('matchups')->get($cacheKey);

        // if ($matchups) {
        //     return $matchups;
        // } else {
        //     $lineups = collect($this->nbaExternalService->getTodayLineups());

        //     $games = $lineups->map(fn($game) => [
        //         'homeTeamId' => $game->homeTeam->teamId,
        //         'awayTeamId' => $game->awayTeam->teamId,
        //     ]);

        //     $awayTeamIds = $games->map(fn($game) => $game['awayTeamId'])->toArray();
        //     $awayTeams = $this->nbaTeamRepository->getTeamsDataForMatchups($awayTeamIds, false);

        //     $homeTeamIds = $games->map(fn($game) => $game['homeTeamId'])->toArray();
        //     $homeTeams = $this->nbaTeamRepository->getTeamsDataForMatchups($homeTeamIds);

        //     $matchups = $games->mapWithKeys(function ($game) use ($awayTeams, $homeTeams) {
        //         $homeTeam = $homeTeams->firstWhere('external_id', $game['homeTeamId']);
                
        //         return [
        //             $homeTeam->market_id => [
        //                 'away_team' => $awayTeams->firstWhere('external_id', $game['awayTeamId']),
        //                 'home_team' => $homeTeam,
        //             ]
        //         ];
        //     });
        // }

        // return $matchups;
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

    public function syncTeamMarkets(): void
    {
        $prefix = 'https://bv2-us.digitalsportstech.com/api/sgmMarkets/gfm/grouped?sb=juancito&gameId=';

        $markets = DB::connection('markets')
            ->table('nfl_markets')
            ->where('url', 'like', $prefix . '%')
            ->orderBy('game_id')
            ->get();

        foreach ($markets as $marketRecord) {
            $gameId = $marketRecord->game_id;
            $payload = json_decode($marketRecord->body, true);

            if (!is_array($payload)) {
                Log::warning('NFL market inválido: JSON no parseable', ['game_id' => $gameId]);
                continue;
            }

            $toWin = collect($payload)->firstWhere('statistic', 'to win');

            if (empty($toWin['markets'])) {
                Log::warning('Mercado sin estadística to win', ['game_id' => $gameId]);
                continue;
            }

            $gameInfo = $this->extractGameInfo($toWin['markets']);

            if (!$gameInfo) {
                Log::warning('Mercado sin información de equipos', ['game_id' => $gameId]);
                continue;
            }

            [$homeMarketId, $awayMarketId] = $gameInfo;

            $homeTeam = NflTeam::where('market_id', $homeMarketId)->first();
            $awayTeam = NflTeam::where('market_id', $awayMarketId)->first();

            if (!$homeTeam || !$awayTeam) {
                Log::warning('No se encontraron equipos para mercado', [
                    'game_id' => $gameId,
                    'home_market_id' => $homeMarketId,
                    'away_market_id' => $awayMarketId,
                ]);
                continue;
            }

            $game = NflGame::where('home_team_id', $homeTeam->id)
                ->where('away_team_id', $awayTeam->id)
                ->whereNull('market_id')
                ->first();

            if (!$game) {
                Log::warning('Mercado sin juego asociado', [
                    'game_id' => $gameId,
                    'home_team_id' => $homeTeam->id,
                    'away_team_id' => $awayTeam->id,
                ]);
                continue;
            }

            $game->update(['market_id' => $gameId]);
            
            $favoriteTeamId = $this->determineFavoriteTeamId($toWin['markets'], $homeTeam->id, $awayTeam->id);

            $handicap = $this->normalizeHandicapValue($this->extractPrincipalValue($payload, 'handicap win'));
            $totalPoints = $this->extractPrincipalValue($payload, 'total points');
            $firstHalfHandicap = $this->normalizeHandicapValue($this->extractPrincipalValue($payload, '1st half handicap win'));
            $firstHalfPoints = $this->extractPrincipalValue($payload, '1st half total points');
            $awaySoloPoints = $this->extractPrincipalValue($payload, 'away team total points');
            $homeSoloPoints = $this->extractPrincipalValue($payload, 'home team total points');

            NflGameMarket::updateOrCreate(
                ['game_id' => $game->id],
                [
                    'favorite_team_id' => $favoriteTeamId,
                    'handicap' => $handicap,
                    'total_points' => $totalPoints,
                    'first_half_handicap' => $firstHalfHandicap,
                    'first_half_points' => $firstHalfPoints,
                    'away_team_solo_points' => $awaySoloPoints,
                    'home_team_solo_points' => $homeSoloPoints,
                ]
            );

            Log::info('Mercado NFL sincronizado', [
                'game_id' => $gameId,
                'nfl_game_id' => $game->id,
            ]);
        }
    }

    public function syncPlayerMarkets()
    {
        $prefix = 'https://bv2-us.digitalsportstech.com/api/dfm/marketsByOu?sb=juancito&gameId=';

        $playersByMarketId = NflPlayer::whereNotNull('market_id')->get()->keyBy('market_id');

        if ($playersByMarketId->isEmpty()) {
            Log::warning('No hay jugadores con market_id asignado, no se sincronizarán mercados de jugadores');
            return;
        }

        $records = DB::connection('markets')
            ->table('nfl_markets')
            ->where('url', 'like', $prefix . '%')
            ->orderBy('game_id')
            ->get();

        $groupedByGame = [];

        foreach ($records as $record) {
            // dd('entro a records');
            $statistic = $this->extractStatisticFromUrl($record->url);

            if (!$statistic || !array_key_exists($statistic, self::PLAYER_STAT_COLUMN_MAP)) {
                continue;
            }

            $payload = json_decode($record->body, true);

            Log::info('Procesando juego ' . $record->game_id . ' con statistic ' . $statistic);

            if (!is_array($payload)) {
                Log::warning('Mercado de jugador inválido: JSON no parseable', [
                    'game_id' => $record->game_id,
                    'statistic' => $statistic,
                    'url' => $record->url,
                ]);
                continue;
            }

            $groupedByGame[$record->game_id][] = [
                'statistic' => $statistic,
                'column' => self::PLAYER_STAT_COLUMN_MAP[$statistic],
                'payload' => $payload,
            ];
        }

        foreach ($groupedByGame as $marketId => $statBlocks) {
            $game = NflGame::where('market_id', $marketId)->first();

            if (!$game) {
                Log::warning('Mercado de jugador sin juego asociado', ['game_id' => $marketId]);
                continue;
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
                            Log::info('Jugador sin valor principal en mercado', [
                                'game_id' => $marketId,
                                'statistic' => $block['statistic'],
                                'player_market_id' => $playerMarketId,
                                'player_name' => $playerBlock['name'] ?? null,
                            ]);
                            continue;
                        }

                        $player = $playersByMarketId->get($playerMarketId);

                        if (!$player) {
                            Log::warning('Jugador de mercado no encontrado', [
                                'game_id' => $marketId,
                                'statistic' => $block['statistic'],
                                'player_market_id' => $playerMarketId,
                                'player_name' => $playerBlock['name'] ?? null,
                                'team' => $playerBlock['team'] ?? null,
                            ]);
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
                Log::info('Sin datos de jugadores para el juego', ['game_id' => $marketId, 'nfl_game_id' => $game->id]);
                continue;
            }

            $count = 0;

            foreach ($playerRows as $row) {
                $count++;

                NflPlayerMarket::updateOrCreate([
                    'game_id' => $row['game_id'],
                    'player_id' => $row['player_id'],
                ], [
                    'passing_yards' => $row['passing_yards'] ?? null,
                    'pass_completions' => $row['pass_completions'] ?? null,
                    'pass_attempts' => $row['pass_attempts'] ?? null,
                    'rushing_yards' => $row['rushing_yards'] ?? null,
                    'carries' => $row['carries'] ?? null,
                    'receiving_yards' => $row['receiving_yards'] ?? null,
                    'receptions' => $row['receptions'] ?? null,
                    'tackles' => $row['tackles'] ?? null,
                    'sacks' => $row['sacks'] ?? null,
                ]);
            }

            Log::info('Mercados de jugadores sincronizados', [
                'game_id' => $marketId,
                'nfl_game_id' => $game->id,
                'jugadores' => $count,
            ]);
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

    private function extractStatisticFromUrl(string $url): ?string
    {
        $components = parse_url($url);

        if (empty($components['query'])) {
            return null;
        }

        parse_str($components['query'], $params);

        if (!isset($params['statistic'])) {
            return null;
        }

        return urldecode($params['statistic']);
    }

    private function formatDecimalValue(float|int|string $value): string
    {
        $numeric = (float) $value;

        return number_format($numeric, 1, '.', '');
    }
}
