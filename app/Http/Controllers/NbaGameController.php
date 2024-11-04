<?php

namespace App\Http\Controllers;

use App\Enums\LeagueEnum;
use App\Http\Requests\StoreNbaGameRequest;
use App\Models\NbaGame;
use App\Models\NbaTeam;
use App\Repositories\NbaJsonRepository;
use App\Repositories\NbaScoreRepository;
use App\Services\NbaGameService;
use App\Services\SportsnetService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class NbaGameController extends Controller
{
    public function __construct(
        protected NbaGameService $nbaGameService,
        protected SportsnetService $sportsnetService,
    ) {
    }

    public function index()
    {
        // return $response = Http::get('https://bv2-us.digitalsportstech.com/api/dfm/marketsByOu?sb=juancito&gameId=234691&statistic=Points')->json();

        $response = Http::get($this->sportsnetService->getGameUrl('nba', '10dee9b6-c101-4bba-82af-f43f3dcfff30'));

        return $response->json('data.game');

    }

    public function store(StoreNbaGameRequest $request)
    {
        return $this->nbaGameService->create($request->validated());
    }

    public function show(NbaGame $nbaGame)
    {
        //
    }

    public function update(Request $request, NbaGame $nbaGame)
    {
        //
    }

    public function destroy(NbaGame $nbaGame)
    {
        //
    }
}
