<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportWnbaGamesRequest;
use App\Models\WnbaGame;
use App\Models\WnbaPlayer;
use App\Services\WnbaExternalService;
use App\Services\WnbaMarketService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class WnbaGameController extends Controller
{
    public function __construct(
        protected WnbaExternalService $wnbaExternalService,
        protected WnbaMarketService $wnbaMarketService,
    ) {
    }

    public function index(Request $request)
    {
        if ($request->is('*/markets*')) {
            return $this->wnbaMarketService->getLiveMarkets($request->query('date'));
        }

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

    public function matchups(Request $request)
    {
        return $this->wnbaMarketService->getMatchups($request->query('date'));
    }

    public function sync(Request $request)
    {
        $marketId = $request->input('market_id');

        $this->wnbaMarketService->syncMarkets($marketId);

        return response()->json([
            'status' => 'ok',
            'message' => 'Sincronización de mercados WNBA completada',
            'market_id' => $marketId,
        ]);
    }

    public function syncWnbaPlayers()
    {
        return $this->wnbaMarketService->syncPlayers();
    }

    public function getScores(WnbaPlayer $player)
    {
        return $player->load([
            'stats' => function ($query) {
                $query->with([
                    'game' => fn ($gameQuery) => $gameQuery->with(['awayTeam', 'homeTeam', 'stats']),
                ])
                    ->orderByDesc('wnba_player_stats.id')
                    ->take(16);
            },
        ]);
    }
}
