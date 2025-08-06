<?php

namespace App\Http\Controllers;


use App\Http\Requests\ImportWnbaGamesRequest;
use App\Services\NbaExternalService;
use App\Services\NbaGameService;
use Illuminate\Http\Request;

class WnbaGameController extends Controller
{

    public function __construct(
        protected NbaExternalService $nbaExternalService,
        protected NbaGameService $nbaGameService,
    ) {
    }

    public function importByDateRange(ImportWnbaGamesRequest $request)
    {
        $this->nbaGameService->importGamesByDateRange($request->validated());
    }
}
