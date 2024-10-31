<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNbaGameRequest;
use App\Models\NbaGame;
use App\Models\NbaTeam;
use App\Repositories\NbaJsonRepository;
use App\Repositories\NbaScoreRepository;
use App\Services\NbaGameService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class NbaGameController extends Controller
{
    public function __construct(
        protected NbaGameService $nbaGameService,
    ) {
    }

    public function index()
    {
        // return $response = Http::get('https://bv2-us.digitalsportstech.com/api/dfm/marketsByOu?sb=juancito&gameId=234691&statistic=Points')->json();
        return $game = Cache::get('test')['data']['game'];

        $away = $game['visiting_team'];

        $home = $game['home_team'];

        $players = array_merge($away['starters'], $away['bench']);
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
