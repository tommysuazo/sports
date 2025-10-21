<?php

namespace App\Http\Controllers;

use App\Models\NbaTeam;
use App\Services\NbaTeamService;
use Illuminate\Http\Request;

class NbaTeamController extends Controller
{
    public function __construct(
        protected NbaTeamService $nbaTeamService,
    ) {
    }

    public function getStats(NbaTeam $team)
    {
        return $this->nbaTeamService->getTeamStats($team);
    }

    public function getAverageStatsAll()
    {
        return $this->nbaTeamService->getTeamsAverageStats();
    }

    public function getAverageStats(NbaTeam $team)
    {
        return $this->nbaTeamService->getTeamAverageStats($team);
    }

    public function getRecentPerformance(Request $request)
    {
        $games = max(1, (int) $request->integer('games', 7));

        return $this->nbaTeamService->getTeamsRecentPerformance($games);
    }
}
