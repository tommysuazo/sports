<?php

namespace App\Http\Controllers;

use App\Services\NflMarketService;
use Illuminate\Http\Request;

class NflMarketController extends Controller
{
    public function __construct(
        protected NflMarketService $nflMarketService,
    ) {
    }

    public function index(Request $request)
    {
        return $this->nflMarketService->getLiveMarkets();
    }

    public function matchups(Request $request)
    {
        return $this->nflMarketService->getMatchups($request->input('week'));
    }

    public function sync()
    {
        return $this->nflMarketService->syncMarkets();
    }
}
