<?php

namespace App\Services;

use App\Models\NbaTeam;
use App\Repositories\NbaTeamStatRepository;

class NbaTeamService
{
    /**
     * Métricas consideradas para ranking ofensivo/defensivo.
     *
     * @var array<int, string>
     */
    protected array $rankingMetrics = [
        'points',
        'assists',
        'rebounds',
    ];

    public function __construct(
        protected NbaTeamStatRepository $nbaTeamStatRepository,
    ) {
    }

    public function getTeamStats(NbaTeam $team): NbaTeam
    {
        return $this->nbaTeamStatRepository->loadWithStats($team);
    }

    public function getTeamAverageStats(NbaTeam $team): array
    {
        return [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'short_name' => $team->short_name,
                'city' => $team->city,
            ],
            'games_with_stats' => $this->nbaTeamStatRepository->countStats($team),
            'averages' => $this->nbaTeamStatRepository->getAverageStats($team),
        ];
    }

    public function getTeamsAverageStats(): array
    {
        $teams = NbaTeam::orderBy('name')->get(['id', 'name', 'short_name', 'city']);
        $aggregated = $this->nbaTeamStatRepository->getAverageStatsForAllTeams();

        $teamsData = $teams->mapWithKeys(function (NbaTeam $team) use ($aggregated) {
            $stats = $aggregated[$team->id] ?? null;
            $forValues = $stats['averages']['for'] ?? [];
            $againstValues = $stats['averages']['against'] ?? [];

            return [
                $team->id => [
                    'team' => [
                        'id' => $team->id,
                        'name' => $team->name,
                        'short_name' => $team->short_name,
                        'city' => $team->city,
                    ],
                    'games_with_stats' => (int) ($stats['games_with_stats'] ?? 0),
                    'averages' => [
                        'for' => $this->normalizeMetrics($forValues),
                        'against' => $this->normalizeMetrics($againstValues),
                    ],
                    'rankings' => [
                        'offense' => array_fill_keys($this->rankingMetrics, null),
                        'defense' => array_fill_keys($this->rankingMetrics, null),
                    ],
                ],
            ];
        })->all();

        $this->assignRankings($teamsData, 'for', 'offense', true);
        $this->assignRankings($teamsData, 'against', 'defense', false);

        return [
            'teams' => array_values(array_map(function (array $data) {
                return [
                    'team' => $data['team'],
                    'games_with_stats' => $data['games_with_stats'],
                    'ofensive' => $this->buildMetricResponse($data['averages']['for'], $data['rankings']['offense']),
                    'defensive' => $this->buildMetricResponse($data['averages']['against'], $data['rankings']['defense']),
                ];
            }, $teamsData)),
        ];
    }

    protected function normalizeMetrics(array $values): array
    {
        $defaults = array_fill_keys($this->rankingMetrics, null);
        $filtered = array_intersect_key($values, $defaults);

        return array_replace($defaults, $filtered);
    }

    protected function buildMetricResponse(array $values, array $ranks): array
    {
        $response = [];

        foreach ($this->rankingMetrics as $metric) {
            $response[$metric] = [
                'value' => $values[$metric],
                'rank' => $ranks[$metric],
            ];
        }

        return $response;
    }

    protected function assignRankings(array &$teamsData, string $averageSide, string $rankingSide, bool $descending): void
    {
        foreach ($this->rankingMetrics as $metric) {
            $collection = collect($teamsData)
                ->filter(fn (array $data) => $data['averages'][$averageSide][$metric] !== null);

            $sorted = $descending
                ? $collection->sortByDesc(fn (array $data) => $data['averages'][$averageSide][$metric])
                : $collection->sortBy(fn (array $data) => $data['averages'][$averageSide][$metric]);

            foreach ($sorted->keys()->values() as $index => $teamId) {
                $teamsData[$teamId]['rankings'][$rankingSide][$metric] = $index + 1;
            }
        }
    }
}
