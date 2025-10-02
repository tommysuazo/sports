<?php

namespace App\Services;

use App\Models\NflGame;
use App\Repositories\NflGameRepository;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class NflGameService
{
    public function __construct(
        protected NflExternalService $nflExternalService
    ){
    }

    public function list(array $filters)
    {
        $query = NflGame::with([
            'awayTeam' => fn($qs) => $qs->select([
                'nfl_teams.id',
                'nfl_teams.code',
            ]),
            'homeTeam' => fn($qs) => $qs->select([
                'nfl_teams.id',
                'nfl_teams.code',
            ]),
            'market',
            'stats' => fn($qs) => $qs->select([
                'nfl_team_stats.id',
                'nfl_team_stats.game_id',
                'nfl_team_stats.team_id',
                'nfl_team_stats.is_away',
                'nfl_team_stats.points_total',
            ]),
        ])
            ->where('is_completed', true);
        
        if (!empty($filters['team'])) {
            $query->where(
                fn($queryTeam) => $queryTeam->where('away_team_id', $filters['team'])
                    ->orWhere('home_team_id', $filters['team'])
            );
        }

        return $query->orderByDesc('id')->get();
    }

    public function importGamesByDateRange(array $data)
    {
        $year = $data['year'] ?? 2025;

        for ($week = $data['from']; $week <= $data['to']; $week++) {
            $this->nflExternalService->importGamesByWeek($week, $year);
        }
    }
}