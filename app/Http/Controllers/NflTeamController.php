<?php

namespace App\Http\Controllers;

use App\Models\NflTeam;
use App\Services\NflTeamService;
use Illuminate\Http\Request;

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

    public function getAverageStatsAll(Request $request)
    {
        $games = max(1, (int) $request->integer('games', 7));

        return $this->nflTeamService->getTeamsAverageStats($games);
    }

    public function getAverageStats(NflTeam $team)
    {
        return $this->nflTeamService->getTeamAverageStats($team);
    }
}
