<?php

namespace App\Services;

use App\Models\NbaGame;
use App\Models\NbaInjury;
use App\Models\NbaPlayer;
use App\Models\NbaTeam;
use App\Repositories\NbaGameRepository;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class NbaGameService
{
    public function __construct(
        protected NbaExternalService $NbaExternalService,
        protected NbaGameRepository $nbaGameRepository,
    ){
    }

    public function list(array $filters)
    {
        $query = NbaGame::with([
            'awayTeam' => fn ($qs) => $qs->select([
                'nba_teams.id',
                'nba_teams.short_name',
            ]),
            'homeTeam' => fn ($qs) => $qs->select([
                'nba_teams.id',
                'nba_teams.short_name',
            ]),
            'market',
            'stats'
        ])
            ->where('is_completed', true);

        if (!empty($filters['team'])) {
            $query->where(fn ($queryTeam) => $queryTeam
                ->where('away_team_id', $filters['team'])
                ->orWhere('home_team_id', $filters['team'])
            );
        }

        return $query->orderByDesc('id')->paginate(10);
    }

    public function importGamesByDateRange(array $data)
    {
        $period = CarbonPeriod::create(Carbon::parse($data['from']), Carbon::parse($data['to']));

        foreach ($period as $date) {
            $this->NbaExternalService->importGamesByDate($date);
        }
    }

    public function getLineups(array $data = []): array
    {
        $lineups = $this->NbaExternalService->getTodayLineups();

        $gamesPayload = collect($lineups['games'] ?? []);

        if ($gamesPayload->isEmpty()) {
            return [
                'games' => [],
            ];
        }

        $gameExternalIds = $gamesPayload
            ->pluck('gameId')
            ->filter()
            ->unique()
            ->values();

        $teamExternalIds = $gamesPayload
            ->flatMap(function (array $gamePayload) {
                return [
                    data_get($gamePayload, 'homeTeam.teamId'),
                    data_get($gamePayload, 'awayTeam.teamId'),
                ];
            })
            ->filter()
            ->unique()
            ->values();

        $playerExternalIds = $gamesPayload
            ->flatMap(function (array $gamePayload) {
                return collect([
                    data_get($gamePayload, 'homeTeam.players', []),
                    data_get($gamePayload, 'awayTeam.players', []),
                ])
                    ->flatten(1)
                    ->pluck('personId');
            })
            ->filter()
            ->unique()
            ->values();

        $games = NbaGame::with(['homeTeam', 'awayTeam'])
            ->whereIn('external_id', $gameExternalIds->all())
            ->get()
            ->keyBy('external_id');

        $teams = NbaTeam::whereIn('external_id', $teamExternalIds->all())
            ->get()
            ->keyBy('external_id');

        $players = NbaPlayer::whereIn('external_id', $playerExternalIds->all())
            ->get()
            ->keyBy('external_id');

        $gamesResponse = $gamesPayload
            ->map(function (array $gamePayload) use ($games, $teams, $players) {
                $game = $games->get($gamePayload['gameId']);
                $homeTeamPayload = data_get($gamePayload, 'homeTeam', []);
                $awayTeamPayload = data_get($gamePayload, 'awayTeam', []);

                $homeTeamModel = $teams->get($homeTeamPayload['teamId'] ?? null) ?? $game?->homeTeam;
                $awayTeamModel = $teams->get($awayTeamPayload['teamId'] ?? null) ?? $game?->awayTeam;

                $inactivePlayerPayloads = collect([
                        $homeTeamPayload['players'] ?? [],
                        $awayTeamPayload['players'] ?? [],
                    ])
                    ->flatten(1)
                    ->filter(static fn (array $playerPayload) => strcasecmp($playerPayload['rosterStatus'] ?? '', 'Inactive') === 0)
                    ->values();

                $inactivePlayers = $inactivePlayerPayloads
                    ->map(static fn (array $playerPayload) => $playerPayload['personId'] ?? null)
                    ->filter()
                    ->map(fn (int|string $externalPlayerId) => $players->get($externalPlayerId))
                    ->filter();

                if ($game) {
                    $injuredPlayerIds = $inactivePlayers->pluck('id')->filter()->values()->all();

                    if (empty($injuredPlayerIds)) {
                        NbaInjury::where('game_id', $game->id)->delete();
                    } else {
                        NbaInjury::where('game_id', $game->id)
                            ->whereNotIn('player_id', $injuredPlayerIds)
                            ->delete();

                        foreach ($injuredPlayerIds as $playerId) {
                            NbaInjury::firstOrCreate([
                                'game_id' => $game->id,
                                'player_id' => $playerId,
                            ]);
                        }
                    }
                }

                $mapPlayer = static function (array $playerPayload) use ($players) {
                    $playerModel = $players->get($playerPayload['personId'] ?? null);
                    $fallbackName = trim(($playerPayload['playerName'] ?? '') !== ''
                        ? $playerPayload['playerName']
                        : trim(($playerPayload['firstName'] ?? '') . ' ' . ($playerPayload['lastName'] ?? '')));

                    return [
                        'id' => $playerModel?->id,
                        'name' => $playerModel?->full_name ?: ($fallbackName !== '' ? $fallbackName : null),
                    ];
                };

                $transformTeam = static function (array $teamPayload, ?NbaTeam $teamModel) use ($mapPlayer) {
                    $playerCollection = collect($teamPayload['players'] ?? []);

                    $activePlayers = $playerCollection
                        ->filter(static fn (array $playerPayload) => strcasecmp($playerPayload['rosterStatus'] ?? '', 'Active') === 0)
                        ->values();

                    return [
                        'id' => $teamModel?->id,
                        'name' => $teamModel?->name
                            ?? $teamPayload['teamName']
                            ?? $teamPayload['teamAbbreviation']
                            ?? null,
                        'starters' => $activePlayers->take(5)->map($mapPlayer)->values()->all(),
                        'bench' => $activePlayers->skip(5)->map($mapPlayer)->values()->all(),
                        'injuries' => $playerCollection
                            ->filter(static fn (array $playerPayload) => strcasecmp($playerPayload['rosterStatus'] ?? '', 'Inactive') === 0)
                            ->map($mapPlayer)
                            ->values()
                            ->all(),
                    ];
                };
                return [
                    'id' => $game?->id,
                    'away_team' => $transformTeam($awayTeamPayload, $awayTeamModel),
                    'home_team' => $transformTeam($homeTeamPayload, $homeTeamModel),
                ];
            })
            ->values()
            ->all();

        return [
            'games' => $gamesResponse,
        ];
    }
}
