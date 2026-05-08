<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportWnbaGamesRequest;
use App\Models\WnbaGame;
use App\Services\WnbaExternalService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class WnbaGameController extends Controller
{
    public function __construct(
        protected WnbaExternalService $wnbaExternalService,
    ) {
    }

    public function index(Request $request)
    {
        return WnbaGame::with(['awayTeam', 'homeTeam', 'market', 'stats'])
            ->when($request->input('team'), function ($query, $teamId) {
                $query->where(fn ($teamQuery) => $teamQuery
                    ->where('away_team_id', $teamId)
                    ->orWhere('home_team_id', $teamId)
                );
            })
            ->where('is_completed', true)
            ->orderByDesc('start_at')
            ->paginate(10);
    }

    public function importByDateRange(ImportWnbaGamesRequest $request)
    {
        $data = $request->validated();
        $period = CarbonPeriod::create(Carbon::parse($data['from']), Carbon::parse($data['to']));

        foreach ($period as $date) {
            $this->wnbaExternalService->importGamesByDate($date);
        }
    }

    public function getLineups()
    {
        return response()->json(WnbaExternalService::getTodayLineups());
    }
}
