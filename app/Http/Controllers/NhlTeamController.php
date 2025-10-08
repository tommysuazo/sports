<?php

namespace App\Http\Controllers;

use App\Models\NhlTeam;
use App\Services\NhlTeamService;

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

    public function getAverageStatsAll()
    {
        return $this->nhlTeamService->getTeamsAverageStats();
    }

    public function getAverageStats(NhlTeam $team)
    {
        return $this->nhlTeamService->getTeamAverageStats($team);
    }
}
