<?php

namespace App\Http\Controllers;

use App\Models\NflTeam;
use App\Services\NflTeamService;

class NflTeamController extends Controller
{
    public function __construct(
        protected NflTeamService $nflTeamService,
    ) {
    }

    public function getStats(NflTeam $team)
    {
        return $this->nflTeamService->getTeamStats($team);
    }

    public function getAverageStatsAll()
    {
        return $this->nflTeamService->getTeamsAverageStats();
    }

    public function getAverageStats(NflTeam $team)
    {
        return $this->nflTeamService->getTeamAverageStats($team);
    }
}
