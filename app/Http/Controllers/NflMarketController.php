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

    public function index()
    {
        return $this->nflMarketService->getLiveMarkets();
    }

    public function matchups()
    {
        return $this->nflMarketService->getMatchups();
    }

    public function sync()
    {
        return $this->nflMarketService->syncMarkets();
    }
}
