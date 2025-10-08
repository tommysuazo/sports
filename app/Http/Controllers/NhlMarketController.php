<?php

namespace App\Http\Controllers;

use App\Services\NhlMarketService;
use Illuminate\Http\Request;

class NhlMarketController extends Controller
{
    public function __construct(
        protected NhlMarketService $nhlMarketService,
    ) {
    }

    public function index(Request $request)
    {
        $date = $request->query('date');

        return $this->nhlMarketService->getLiveMarkets($date);
    }

    public function matchups(Request $request)
    {
        $date = $request->query('date');

        return $this->nhlMarketService->getMatchups($date);
    }
}
