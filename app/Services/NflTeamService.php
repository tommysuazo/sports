<?php

namespace App\Services;

use App\Models\NflTeam;
use App\Models\NflTeamStat;
use App\Repositories\NflTeamStatRepository;
use Illuminate\Support\Collection;

class NflTeamService
{
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

    public function getTeamsAverageStats(int $games = 7): array
    {
        return $this->getTeamsRecentPerformance($games);
    }

    public function getTeamsRecentPerformance(int $games = 7): array
    {
        $teams = NflTeam::orderBy('name')->get(['id', 'name', 'code', 'city']);

        $teamsData = $teams->map(function (NflTeam $team) use ($games) {
            $recentStats = $this->nflTeamStatRepository->getRecentStatsWithGameData($team, $games);

            $record = $this->calculateRecentRecord($recentStats, $team->id);
            $ats = $this->calculateAgainstTheSpreadRecord($recentStats, $team->id);
            $overUnder = $this->calculateTotalsRecord($recentStats, $team->id);

            return [
                'team' => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'code' => $team->code,
                    'city' => $team->city,
                ],
                'requested_games' => $games,
                'records' => [
                    'last_games' => [
                        'games_evaluated' => $record['games_evaluated'],
                        'wins' => $record['wins'],
                        'losses' => $record['losses'],
                    ],
                    'ats' => [
                        'wins' => $ats['wins'],
                        'losses' => $ats['losses'],
                        'pushes' => $ats['pushes'],
                        'games_evaluated' => $ats['games_evaluated'],
                    ],
                    'over_under' => [
                        'over' => $overUnder['over'],
                        'under' => $overUnder['under'],
                        'pushes' => $overUnder['pushes'],
                        'games_evaluated' => $overUnder['games_evaluated'],
                    ],
                ],
                'summary' => $this->buildPerformanceSummary($games, $record, $ats, $overUnder),
            ];
        })->values();

        return [
            'teams' => $teamsData->toArray(),
        ];
    }

    protected function calculateRecentRecord(Collection $stats, int $teamId): array
    {
        $wins = 0;
        $losses = 0;

        foreach ($stats as $stat) {
            $teamPoints = $this->castNullableFloat($stat->points_total);
            $opponentPoints = $this->resolveOpponentPoints($stat, $teamId);

            if ($teamPoints === null || $opponentPoints === null) {
                continue;
            }

            if ($teamPoints > $opponentPoints) {
                $wins++;
            } elseif ($teamPoints < $opponentPoints) {
                $losses++;
            }
        }

        return [
            'wins' => $wins,
            'losses' => $losses,
            'games_evaluated' => $wins + $losses,
        ];
    }

    protected function calculateAgainstTheSpreadRecord(Collection $stats, int $teamId): array
    {
        $wins = 0;
        $losses = 0;
        $pushes = 0;

        foreach ($stats as $stat) {
            $game = $stat->game;
            $market = $game?->market;

            if (!$game || !$market || $market->favorite_team_id === null || $market->handicap === null) {
                continue;
            }

            $teamPoints = $this->castNullableFloat($stat->points_total);
            $opponentPoints = $this->resolveOpponentPoints($stat, $teamId);
            $handicap = $this->castNullableFloat($market->handicap);

            if ($teamPoints === null || $opponentPoints === null || $handicap === null) {
                continue;
            }

            $isFavorite = (int) $market->favorite_team_id === $teamId;
            $margin = $teamPoints - $opponentPoints;
            $spreadResult = $isFavorite ? $margin - $handicap : $margin + $handicap;

            if ($spreadResult > 0) {
                $wins++;
            } elseif ($spreadResult < 0) {
                $losses++;
            } else {
                $pushes++;
            }
        }

        return [
            'wins' => $wins,
            'losses' => $losses,
            'pushes' => $pushes,
            'games_evaluated' => $wins + $losses + $pushes,
        ];
    }

    protected function calculateTotalsRecord(Collection $stats, int $teamId): array
    {
        $overs = 0;
        $unders = 0;
        $pushes = 0;

        foreach ($stats as $stat) {
            $game = $stat->game;
            $market = $game?->market;

            if (!$game || !$market || $market->total_points === null) {
                continue;
            }

            $teamPoints = $this->castNullableFloat($stat->points_total);
            $opponentPoints = $this->resolveOpponentPoints($stat, $teamId);
            $totalLine = $this->castNullableFloat($market->total_points);

            if ($teamPoints === null || $opponentPoints === null || $totalLine === null) {
                continue;
            }

            $totalPoints = $teamPoints + $opponentPoints;

            if ($totalPoints > $totalLine) {
                $overs++;
            } elseif ($totalPoints < $totalLine) {
                $unders++;
            } else {
                $pushes++;
            }
        }

        return [
            'over' => $overs,
            'under' => $unders,
            'pushes' => $pushes,
            'games_evaluated' => $overs + $unders + $pushes,
        ];
    }

    protected function resolveOpponentPoints(NflTeamStat $stat, int $teamId): ?float
    {
        $game = $stat->game;

        if (!$game) {
            return null;
        }

        $opponentStat = $game->stats
            ->first(fn (NflTeamStat $gameStat) => (int) $gameStat->team_id !== $teamId);

        return $this->castNullableFloat($opponentStat?->points_total);
    }

    protected function buildPerformanceSummary(int $gamesRequested, array $record, array $ats, array $overUnder): string
    {
        return sprintf(
            'L%d %d-%d ATS %d-%d-%d O/U %d-%d-%d',
            $gamesRequested,
            $record['wins'] ?? 0,
            $record['losses'] ?? 0,
            $ats['wins'] ?? 0,
            $ats['losses'] ?? 0,
            $ats['pushes'] ?? 0,
            $overUnder['over'] ?? 0,
            $overUnder['under'] ?? 0,
            $overUnder['pushes'] ?? 0
        );
    }

    protected function castNullableFloat($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
