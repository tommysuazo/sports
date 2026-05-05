<?php

namespace App\Http\Controllers;

use App\Models\NhlTeam;
use App\Services\NhlTeamService;
use Illuminate\Http\Request;

class NhlTeamController extends Controller
{
    public function __construct(
        protected NhlTeamService $nhlTeamService,
    ) {
    }

    public function getStats(NhlTeam $team)
    {
        return $this->nhlTeamService->getTeamStats($team);
    }

    public function getAverageStatsAll(Request $request)
    {
        $games = max(1, (int) $request->integer('games', 7));

        return $this->nhlTeamService->getTeamsAverageStats($games);
    }

    public function getAverageStats(NhlTeam $team)
    {
        return $this->nhlTeamService->getTeamAverageStats($team);
    }
}
