<?php

namespace App\Http\Controllers;

use App\Enums\Leagues\LeagueEnum;
use App\Http\Requests\ImportNbaGamesRequest;
use App\Models\League;
use App\Models\NbaGame;
use App\Models\NbaPlayer;
use App\Services\DigitalSportsTechService;
use App\Services\NbaExternalService;
use App\Services\NbaGameService;
use App\Services\SportsnetService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class NbaGameController extends Controller
{
    public function __construct(
        protected NbaExternalService $nbaExternalService,
        protected NbaGameService $nbaGameService,
        protected SportsnetService $sportsnetService,
        protected DigitalSportsTechService $digitalSportsTechService,
    ) {
    }

    public function index()
    {

        // return DigitalSportsTechService::getFakeGamePointsMarket();
        // return response()->json(NbaExternalService::getTeamStats()->json());

        // Cache::tags(['posts', 'homepage'])->put('post_1', ['title' => 'Hola Mundo'], now()->addMinutes(5));

        // $post = Cache::tags(['posts', 'homepage'])->get('post_1');

        // dd($post);

        // return NbaExternalService::getGameByid('1022500123')->json();

        // return NbaExternalService::getTodayLineups();
        return $this->nbaExternalService->getGameDay(Carbon::parse('2025-04-05'))->json();
        // return $this->digitalSportsTechService->syncNbaPlayerMarketIds();
        // return NbaExternalService::getGameDayUrl(Carbon::parse('2025-02-20'))->json();
        // return $this->sportsnetService->getTeamPlayersUrl(LeagueEnum::NBA->value, '583ecb8f-fb46-11e1-82cb-f4ce4684ea4c');
        // $response = Http::withHeaders([
        //     "ocp-apim-subscription-key" => "747fa6900c6c4e89a58b81b72f36eb96",
        //     "Accept" => " */*",
        //     "Accept-Encoding" => " gzip, deflate, br, zstd",
        //     "Accept-Language" => " es-ES,es;q=0.9",
        //     "Cache-Control" => " no-cache",
        //     "Connection" => " keep-alive",
        //     "Host" => " stats.nba.com",
        //     "Origin" => " https://www.nba.com",
        //     "Pragma" => " no-cache",
        //     "Referer" => " https://www.nba.com/",
        //     "Sec-Fetch-Dest" => " empty",
        //     "Sec-Fetch-Mode" => " cors",
        //     "Sec-Fetch-Site" => " same-site",
        //     "User-Agent" => " Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36",
        //     "sec-ch-ua"=> "\"Not(A:Brand\";v=\"99\", \"Google Chrome\";v=\"133\", \"Chromium\";v=\"133\"",
        //     "sec-ch-ua-mobile" => " ?0",
        //     "sec-ch-ua-platform" => "Windows",
        // ])
        //     ->get("https://stats.nba.com/stats/playerindex?LeagueID=00&Season=2024-25");

        // return $response->json('resultSets.0.rowSet');

        return Http::get($this->digitalSportsTechService->getTeamPlayersUrl(LeagueEnum::NBA->value, 'CHI'))->json();

        $playerMarkets = Cache::get('market')[0]['players'];
  
        foreach ($playerMarkets as $playerMarket) {
            $value = $playerMarket['markets'][0]['value'];
            NbaPlayer::where('market_id', $playerMarket['id'])->update(['point_market' => $value]);
        }
    }

    public function importByDateRange(ImportNbaGamesRequest $request)
    {
        
        $this->nbaGameService->importGamesByDateRange($request->validated());
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
