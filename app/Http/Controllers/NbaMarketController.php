<?php

namespace App\Http\Controllers;

use App\Http\Resources\NBA\NbaGameMatchupResource;
use App\Services\NbaMarketService;
use Illuminate\Http\Request;

class NbaMarketController extends Controller
{
    public function __construct(
        protected NbaMarketService $nbaMarketService,
    ) {
    }

    public function index()
    {
        return $this->nbaMarketService->getMarkets();
    }

    public function matchups()
    {
        return $this->nbaMarketService->getMatchups();
        
        // return NbaGameMatchupResource::collection($this->nbaMarketService->getMatchups());
    }

    public function sync()
    {
        return $this->nbaMarketService->sync();
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
