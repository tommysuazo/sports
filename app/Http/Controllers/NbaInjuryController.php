<?php

namespace App\Http\Controllers;

use App\Services\NbaInjuryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NbaInjuryController extends Controller
{

    public function __construct(
        protected NbaInjuryService $nbaInjuryService,
    ) {
    }

    public function index()
    {
        // $currentGames = Nba
        // return NbaInjury::all();
    }

    public function updateTodayInjuries(): JsonResponse
    {
        $summary = $this->nbaInjuryService->syncTodayInjuries();

        return response()->json($summary);
    }
}
