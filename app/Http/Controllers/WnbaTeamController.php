<?php

namespace App\Http\Controllers;

use App\Models\WnbaTeam;
use App\Services\WnbaTeamService;
use Illuminate\Http\Request;

class WnbaTeamController extends Controller
{
    public function __construct(
        protected WnbaTeamService $wnbaTeamService,
    ) {
    }

    public function getStats(WnbaTeam $team)
    {
        return $this->wnbaTeamService->getTeamStats($team);
    }

    public function getAverageStatsAll()
    {
        return $this->wnbaTeamService->getTeamsAverageStats();
    }

    public function getAverageStats(WnbaTeam $team)
    {
        return $this->wnbaTeamService->getTeamAverageStats($team);
    }

    public function getRecentPerformance(Request $request)
    {
        $games = max(1, (int) $request->integer('games', 7));

        return $this->wnbaTeamService->getTeamsRecentPerformance($games);
    }
}
