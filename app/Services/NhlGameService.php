<?php

namespace App\Services;

use App\Models\NhlGame;

class NhlGameService
{
    public function list(array $filters)
    {
        $query = NhlGame::with([
            'awayTeam' => fn ($qs) => $qs->select([
                'nhl_teams.id',
                'nhl_teams.code',
            ]),
            'homeTeam' => fn ($qs) => $qs->select([
                'nhl_teams.id',
                'nhl_teams.code',
            ]),
            'market',
            'stats' => fn ($qs) => $qs->select([
                'nhl_team_stats.id',
                'nhl_team_stats.game_id',
                'nhl_team_stats.team_id',
                'nhl_team_stats.goals',
                'nhl_team_stats.shots',
            ]),
        ])
            ->where('is_completed', true);

        if (!empty($filters['team'])) {
            $query->where(fn ($queryTeam) => $queryTeam
                ->where('away_team_id', $filters['team'])
                ->orWhere('home_team_id', $filters['team'])
            );
        }

        return $query->orderByDesc('id')->get();
    }
}
