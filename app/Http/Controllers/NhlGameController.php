<?php

namespace App\Http\Controllers;

use App\Services\NhlGameService;
use Illuminate\Http\Request;

class NhlGameController extends Controller
{
    public function __construct(
        protected NhlGameService $nhlGameService,
    ) {
    }

    public function index(Request $request)
    {
        return $this->nhlGameService->list(
            $request->only(['team', 'page', 'all'])
        );
    }
}
