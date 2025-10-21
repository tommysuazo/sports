<?php

namespace App\Http\Controllers;

use App\Models\NbaTeam;
use App\Services\NbaTeamService;

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
}
