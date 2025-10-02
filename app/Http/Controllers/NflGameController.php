<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportNflGameRequest;
use App\Models\NflGame;
use App\Services\NflGameService;
use Illuminate\Http\Request;

class NflGameController extends Controller
{
    public function __construct(
        protected NflGameService $nflGameService
    ) {
    }

    public function index(Request $request)
    {
        return $this->nflGameService->list(
            $request->only(['team', 'page', 'all'])
        );
    }
    
    public function show(NflGame $nflGame)
    {
        //
    }

    public function update(Request $request, NflGame $nflGame)
    {
        //
    }

    public function destroy(NflGame $nflGame)
    {
        //
    }

    public function import(ImportNflGameRequest $request)
    {
        $this->nflGameService->importGamesByDateRange($request->validated());
    }

}
