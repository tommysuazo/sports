<?php

namespace App\Services;

use App\Models\NhlTeam;
use App\Models\NhlTeamStat;
use App\Repositories\NhlTeamStatRepository;
use Illuminate\Support\Collection;

class NhlTeamService
{
    public function __construct(
        protected NhlTeamStatRepository $nhlTeamStatRepository,
    ) {
    }

    public function getTeamStats(NhlTeam $team): NhlTeam
    {
        return $this->nhlTeamStatRepository->loadWithStats($team);
    }

    public function getTeamAverageStats(NhlTeam $team): array
    {
        return [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'code' => $team->code,
                'city' => $team->city,
            ],
            'games_with_stats' => $this->nhlTeamStatRepository->countStats($team),
            'averages' => $this->nhlTeamStatRepository->getTeamStats($team),
        ];
    }

    public function getTeamsAverageStats(int $games = 7): array
    {
        return $this->getTeamsRecentPerformance($games);
    }

    public function getTeamsRecentPerformance(int $games = 7): array
    {
        $teams = NhlTeam::orderBy('name')->get(['id', 'name', 'code', 'city']);

        $teamsData = $teams->map(function (NhlTeam $team) use ($games) {
            $recentStats = $this->nhlTeamStatRepository->getRecentStatsWithGameData($team, $games);

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
            $game = $stat->game;

            if (!$game) {
                continue;
            }

            $winnerId = $game->winner_team_id ? (int) $game->winner_team_id : null;

            if ($winnerId !== null) {
                if ($winnerId === $teamId) {
                    $wins++;
                } elseif (in_array($winnerId, [(int) $game->home_team_id, (int) $game->away_team_id], true)) {
                    $losses++;
                }
                continue;
            }

            $teamGoals = $this->castNullableFloat($stat->goals);
            $opponentGoals = $this->resolveOpponentGoals($stat, $teamId);

            if ($teamGoals === null || $opponentGoals === null) {
                continue;
            }

            if ($teamGoals > $opponentGoals) {
                $wins++;
            } elseif ($teamGoals < $opponentGoals) {
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

            $teamGoals = $this->castNullableFloat($stat->goals);
            $opponentGoals = $this->resolveOpponentGoals($stat, $teamId);
            $handicap = $this->castNullableFloat($market->handicap);

            if ($teamGoals === null || $opponentGoals === null || $handicap === null) {
                continue;
            }

            $isFavorite = (int) $market->favorite_team_id === $teamId;
            $margin = $teamGoals - $opponentGoals;
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

            $teamGoals = $this->castNullableFloat($stat->goals);
            $opponentGoals = $this->resolveOpponentGoals($stat, $teamId);
            $totalLine = $this->castNullableFloat($market->total_points);

            if ($teamGoals === null || $opponentGoals === null || $totalLine === null) {
                continue;
            }

            $totalGoals = $teamGoals + $opponentGoals;

            if ($totalGoals > $totalLine) {
                $overs++;
            } elseif ($totalGoals < $totalLine) {
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

    protected function resolveOpponentGoals(NhlTeamStat $stat, int $teamId): ?float
    {
        $game = $stat->game;

        if (!$game) {
            return null;
        }

        $opponentStat = $game->stats
            ->first(fn (NhlTeamStat $gameStat) => (int) $gameStat->team_id !== $teamId);

        return $this->castNullableFloat($opponentStat?->goals);
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
