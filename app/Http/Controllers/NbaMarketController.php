<?php

namespace App\Http\Controllers;

use App\Services\NbaMarketService;
use Illuminate\Http\Request;

class NbaMarketController extends Controller
{
    public function __construct(
        protected NbaMarketService $nbaMarketService,
    ) {
    }

    public function index(Request $request)
    {
        $resolvedDate = $request->query('date');

        return $this->nbaMarketService->getLiveMarkets($resolvedDate);
    }

    public function matchups(Request $request)
    {
        $resolvedDate = $request->query('date');

        return $this->nbaMarketService->getMatchups($resolvedDate);
    }

    public function sync(Request $request)
    {
        $marketId = $request->input('market_id');

        $this->nbaMarketService->syncMarkets($marketId);

        return response()->json([
            'status' => 'ok',
            'message' => 'Sincronización de mercados NBA completada',
            'market_id' => $marketId,
        ]);
    }

    public function syncPlayers()
    {
        return $this->nbaMarketService->syncPlayers();
    }

    public function syncWnbaPlayers()
    {
        return $this->nbaMarketService->syncWnbaPlayers();
    }
}
