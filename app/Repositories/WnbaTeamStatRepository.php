<?php

namespace App\Repositories;

use App\Models\WnbaGame;
use App\Models\WnbaTeam;
use App\Models\WnbaTeamStat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class WnbaTeamStatRepository
{
    public function loadWithStats(WnbaTeam $team): WnbaTeam
    {
        return $team->load([
            'stats' => fn ($query) => $query
                ->with(['game' => fn ($gameQuery) => $gameQuery->select(
                    'id',
                    'external_id',
                    'start_at',
                    'is_completed',
                    'home_team_id',
                    'away_team_id'
                )])
                ->orderByDesc('wnba_team_stats.id'),
        ]);
    }

    public function getAverageStats(WnbaTeam $team): array
    {
        return [
            'for' => $this->getAveragesForTeam($team),
            'against' => $this->getAveragesAgainstTeam($team),
        ];
    }

    public function create(array $data, WnbaGame $wnbaGame, WnbaTeam $wnbaTeam): WnbaTeamStat
    {
        return WnbaTeamStat::create(array_merge($data, [
            'game_id' => $wnbaGame->getKey(),
            'team_id' => $wnbaTeam->getKey(),
        ]));
    }

    public function countStats(WnbaTeam $team): int
    {
        return $this->getStatsCountForTeams([$team->id])[$team->id] ?? 0;
    }

    public function getAverageStatsForAllTeams(?array $teamIds = null): array
    {
        $averagesFor = $this->getGroupedAveragesForTeams($teamIds);
        $averagesAgainst = $this->getGroupedAveragesAgainstTeams($teamIds);
        $statsCount = $this->getStatsCountForTeams($teamIds);

        $teamIdsCollection = collect()
            ->merge($averagesFor->keys())
            ->merge($averagesAgainst->keys())
            ->merge(array_keys($statsCount))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        return $teamIdsCollection->mapWithKeys(function (int $teamId) use ($averagesFor, $averagesAgainst, $statsCount) {
            return [
                $teamId => [
                    'averages' => [
                        'for' => $this->castAverageRow($averagesFor->get($teamId)),
                        'against' => $this->castAverageRow($averagesAgainst->get($teamId)),
                    ],
                    'games_with_stats' => (int) ($statsCount[$teamId] ?? 0),
                ],
            ];
        })->toArray();
    }

    public function getRecentStatsWithGameData(WnbaTeam $team, int $limit = 7): Collection
    {
        return WnbaTeamStat::query()
            ->with([
                'game' => static fn ($query) => $query->select(
                    'id',
                    'home_team_id',
                    'away_team_id',
                    'winner_team_id',
                    'start_at',
                    'is_completed'
                ),
                'game.market:id,game_id,favorite_team_id,handicap,points',
                'game.stats:id,game_id,team_id,points',
            ])
            ->where('team_id', $team->id)
            ->whereHas('game', static fn ($query) => $query->where('is_completed', true))
            ->orderByDesc(
                WnbaGame::select('start_at')
                    ->whereColumn('wnba_games.id', 'wnba_team_stats.game_id')
                    ->limit(1)
            )
            ->limit($limit)
            ->get();
    }

    protected function getAveragesForTeam(WnbaTeam $team): array
    {
        $select = $this->buildAverageSelect('team_stats');

        $row = DB::table('wnba_team_stats as team_stats')
            ->selectRaw($select)
            ->where('team_stats.team_id', $team->id)
            ->first();

        return $this->castAverageRow($row);
    }

    protected function getAveragesAgainstTeam(WnbaTeam $team): array
    {
        $select = $this->buildAverageSelect('opponent_stats');

        $row = DB::table('wnba_team_stats as team_stats')
            ->join('wnba_team_stats as opponent_stats', function ($join) {
                $join->on('opponent_stats.game_id', '=', 'team_stats.game_id')
                    ->on('opponent_stats.team_id', '!=', 'team_stats.team_id');
            })
            ->selectRaw($select)
            ->where('team_stats.team_id', $team->id)
            ->first();

        return $this->castAverageRow($row);
    }

    protected function getGroupedAveragesForTeams(?array $teamIds = null)
    {
        $query = DB::table('wnba_team_stats as team_stats')
            ->select('team_stats.team_id');

        foreach ($this->averageColumns() as $column) {
            $query->selectRaw("AVG(team_stats.{$column}) as {$column}");
        }

        if ($teamIds) {
            $query->whereIn('team_stats.team_id', $teamIds);
        }

        return $query
            ->groupBy('team_stats.team_id')
            ->get()
            ->keyBy('team_id');
    }

    protected function getGroupedAveragesAgainstTeams(?array $teamIds = null)
    {
        $query = DB::table('wnba_team_stats as team_stats')
            ->join('wnba_team_stats as opponent_stats', function ($join) {
                $join->on('opponent_stats.game_id', '=', 'team_stats.game_id')
                    ->on('opponent_stats.team_id', '!=', 'team_stats.team_id');
            })
            ->select('team_stats.team_id');

        foreach ($this->averageColumns() as $column) {
            $query->selectRaw("AVG(opponent_stats.{$column}) as {$column}");
        }

        if ($teamIds) {
            $query->whereIn('team_stats.team_id', $teamIds);
        }

        return $query
            ->groupBy('team_stats.team_id')
            ->get()
            ->keyBy('team_id');
    }

    protected function getStatsCountForTeams(?array $teamIds = null): array
    {
        $query = DB::table('wnba_team_stats as team_stats')
            ->select('team_stats.team_id')
            ->selectRaw('COUNT(*) as total_stats');

        if ($teamIds) {
            $query->whereIn('team_stats.team_id', $teamIds);
        }

        return $query
            ->groupBy('team_stats.team_id')
            ->pluck('total_stats', 'team_id')
            ->map(fn ($value) => (int) $value)
            ->toArray();
    }

    protected function buildAverageSelect(string $alias): string
    {
        return collect($this->averageColumns())
            ->map(fn (string $column) => "AVG({$alias}.{$column}) as {$column}")
            ->implode(', ');
    }

    protected function castAverageRow($row): array
    {
        if (!$row) {
            return $this->emptyAverageRow();
        }

        return collect($this->averageColumns())
            ->mapWithKeys(function (string $column) use ($row) {
                $value = $row->{$column} ?? null;

                return [$column => $value !== null ? $this->formatAverage($value) : null];
            })
            ->toArray();
    }

    protected function emptyAverageRow(): array
    {
        return collect($this->averageColumns())
            ->mapWithKeys(fn (string $column) => [$column => null])
            ->toArray();
    }

    protected function averageColumns(): array
    {
        return [
            'points',
            'first_half_points',
            'second_half_points',
            'assists',
            'rebounds',
            'steals',
            'blocks',
            'turnovers',
            'field_goals_made',
            'field_goals_attempted',
            'three_pointers_made',
            'three_pointers_attempted',
            'free_throws_made',
            'free_throws_attempted',
        ];
    }

    protected function formatAverage(float $value): float
    {
        return round((float) $value, 1);
    }
}
