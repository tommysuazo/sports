<?php

namespace App\Services;

use App\Models\NflTeam;
use App\Repositories\NflTeamStatRepository;

class NflTeamService
{
    /** @var array<int, string> */
    protected array $rankingMetrics = [
        'points_total',
        'passing_yards',
        'rushing_yards',
    ];

    public function __construct(
        protected NflTeamStatRepository $nflTeamStatRepository,
    ) {
    }

    public function getTeamStats(NflTeam $team): NflTeam
    {
        return $this->nflTeamStatRepository->loadWithStats($team);
    }

    public function getTeamAverageStats(NflTeam $team): array
    {
        return [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'code' => $team->code,
                'city' => $team->city,
            ],
            'games_with_stats' => $this->nflTeamStatRepository->countStats($team),
            'averages' => $this->nflTeamStatRepository->getAverageStats($team),
        ];
    }

    public function getTeamsAverageStats(): array
    {
        $teams = NflTeam::orderBy('name')->get(['id', 'name', 'code', 'city']);
        $aggregated = $this->nflTeamStatRepository->getAverageStatsForAllTeams();

        $teamsDataCollection = $teams->mapWithKeys(function (NflTeam $team) use ($aggregated) {
            $stats = $aggregated[$team->id] ?? null;
            $forValues = $stats['averages']['for'] ?? [];
            $againstValues = $stats['averages']['against'] ?? [];

            return [
                $team->id => [
                    'team' => [
                        'id' => $team->id,
                        'name' => $team->name,
                        'code' => $team->code,
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
        });

        $teamsDataArray = $teamsDataCollection->all();

        $this->assignRankings($teamsDataArray, 'for', 'offense', true);
        $this->assignRankings($teamsDataArray, 'against', 'defense', false);

        return [
            'teams' => array_values(array_map(function (array $data) {
                return [
                    'team' => $data['team'],
                    'games_with_stats' => $data['games_with_stats'],
                    'ofensive' => $this->buildMetricResponse($data['averages']['for'], $data['rankings']['offense']),
                    'defensive' => $this->buildMetricResponse($data['averages']['against'], $data['rankings']['defense']),
                ];
            }, $teamsDataArray)),
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

    protected function assignRankings(array &$teamsDataArray, string $averageSide, string $rankingSide, bool $descending): void
    {
        foreach ($this->rankingMetrics as $metric) {
            $collection = collect($teamsDataArray)
                ->filter(fn(array $data) => $data['averages'][$averageSide][$metric] !== null);

            $sorted = $descending
                ? $collection->sortByDesc(fn(array $data) => $data['averages'][$averageSide][$metric])
                : $collection->sortBy(fn(array $data) => $data['averages'][$averageSide][$metric]);

            foreach ($sorted->keys()->values() as $index => $teamId) {
                $teamsDataArray[$teamId]['rankings'][$rankingSide][$metric] = $index + 1;
            }
        }
    }
}
