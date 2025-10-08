<?php

namespace App\Repositories;

use App\Models\NhlTeam;
use Illuminate\Support\Facades\DB;

class NhlTeamStatRepository
{
    /**
     * Carga el equipo con sus estadísticas más recientes.
     */
    public function loadWithStats(NhlTeam $team): NhlTeam
    {
        return $team->load([
            'stats' => fn ($query) => $query
                ->with(['game' => fn ($gameQuery) => $gameQuery
                    ->select(
                        'id',
                        'external_id',
                        'season',
                        'start_at',
                        'is_completed',
                        'home_team_id',
                        'away_team_id'
                    )
                ])
                ->orderByDesc('nhl_team_stats.id'),
        ]);
    }

    public function getTeamStats(NhlTeam $team): array
    {
        return [
            'for' => $this->getAveragesForTeam($team),
            'against' => $this->getAveragesAgainstTeam($team),
        ];
    }

    public function countStats(NhlTeam $team): int
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

    public function getStatsCountForTeams(?array $teamIds = null): array
    {
        $query = DB::table('nhl_team_stats as team_stats')
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

    protected function getAveragesForTeam(NhlTeam $team): array
    {
        $select = $this->buildAverageSelect('team_stats');

        $row = DB::table('nhl_team_stats as team_stats')
            ->selectRaw($select)
            ->where('team_stats.team_id', $team->id)
            ->first();

        return $this->castAverageRow($row);
    }

    protected function getAveragesAgainstTeam(NhlTeam $team): array
    {
        $select = $this->buildAverageSelect('opponent_stats');

        $row = DB::table('nhl_team_stats as team_stats')
            ->join('nhl_team_stats as opponent_stats', function ($join) {
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
        $query = DB::table('nhl_team_stats as team_stats')
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
        $query = DB::table('nhl_team_stats as team_stats')
            ->join('nhl_team_stats as opponent_stats', function ($join) {
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
            'goals',
            'shots',
        ];
    }

    protected function formatAverage(float $value): float
    {
        return round((float) $value, 1);
    }
}
