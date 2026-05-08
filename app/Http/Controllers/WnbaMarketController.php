<?php

namespace App\Http\Controllers;

use App\Services\WnbaMarketService;
use Illuminate\Http\Request;

class WnbaMarketController extends Controller
{
    public function __construct(
        protected WnbaMarketService $wnbaMarketService,
    ) {
    }

    public function index(Request $request)
    {
        return $this->wnbaMarketService->getLiveMarkets($request->query('date'));
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
            'message' => 'Sincronizacion de mercados WNBA completada',
            'market_id' => $marketId,
        ]);
    }

    public function syncPlayers()
    {
        return $this->wnbaMarketService->syncPlayers();
    }
}
