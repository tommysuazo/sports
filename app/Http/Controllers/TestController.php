<?php

namespace App\Http\Controllers;

use App\Enums\DigitalSportsTech\DigitalSportsTechNflEnum;
use App\Enums\NflWeekEnum;
use App\Models\NflGame;
use App\Models\NflPlayer;
use App\Models\NflTeam;
use App\Models\NhlTeam;
use App\Services\NbaExternalService;
use App\Services\NbaStatsService;
use App\Services\NflExternalService;
use App\Services\NflMarketService;
use App\Services\NhlExternalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestController extends Controller
{


    public function __invoke()
    {
        return (now());
        // teams list
        // https://api-web.nhle.com/v1/standings/2025-04-17
        
        // teams roster
        // https://api-web.nhle.com/v1/roster/TOR/20252026
        
        // gameday
        // https://api-web.nhle.com/v1/score/2023-11-10

        // game boxscore 
        // https://api-web.nhle.com/v1/gamecenter/2023020204/boxscore


        // $request = Http::get("https://api-web.nhle.com/v1/standings/2025-04-17");

        // $teams = NhlTeam::all();

        Log::info('Inicio de migracion de equipos de NHL');

        $data = NhlExternalService::getTeams();
        
        if (!empty($data['standings'])) {
            foreach ($data['standings'] as $teamData) {
                if (empty($teamData['teamAbbrev']) || empty($teamData['teamAbbrev']['default'])) {
                    continue;
                }

                $allTeamInfo[$teamData['teamAbbrev']['default']] = $teamData;
            }
        }

        ksort($allTeamInfo);

        return $allTeamInfo;
        // resolve(NflMarketService::class)->syncMarkets();

        // return resolve(NflMarketService::class)->getMatchups(NflWeekEnum::WEEK_4);
        
        // return NflGame::with(['homeTeam', 'awayTeam', 'markets', 'playerMarkets',])->where('week', 4)->get();

        // return 'ok';
        // $teams = NflTeam::with('players')->get();

        // foreach ($teams as $team) {
        //     Log::info("Procesando equipo {$team->name}");
        //     $players = $team->players;

        //     $marketPlayers = Http::get("https://bv2-us.digitalsportstech.com/api/player?leagueId=142&teamId={$team->market_id}");

        //     $marketPlayers = collect($marketPlayers->json())->map(fn ($player) => collect($player));

        //     $activePlayers = $marketPlayers->where('isActive', true);
            
        //     $inactivePlayers = $marketPlayers->where('isActive', false);

        //     foreach($activePlayers as $marketPlayer) {
        //         Log::info("Procesando jugador de MERCADO con nombre {$marketPlayer['name']} en {$team->name}");

        //         $player = $players->first(fn ($p) => $p->full_name === $marketPlayer['name']);
                
        //         if (!$player) {
        //             $name = explode(' ', $marketPlayer['name']);

        //             $player = $players->filter(
        //                 fn($player) => $player->first_name === $name[0] && strpos($player->last_name, substr($name[1], 0, 3)) === 0
        //             )->first();

        //             if (!$player) {
        //                 $player = $players->filter(
        //                     fn($player) => $player->last_name === $name[1] && strpos($player->first_name, substr($name[0], 0, 3)) === 0
        //                 )->first();
        //             }
        //         }

        //         if ($player) {
        //             $player->update(['market_id' => $marketPlayer['id']]);
        //             $player->market_id = $marketPlayer['id'];

        //         } elseif (!$player) {
        //             Log::info("No se encontró jugador para {$marketPlayer['name']} en {$team->name}");
        //         }
        //     }

        //     foreach($players->whereNull('market_id') as $player) {
        //         Log::info("Procesando jugador EXTERNAL con nombre {$player->full_name} en {$team->name}");

        //         $marketPlayer = $inactivePlayers->first(fn ($mp) => $mp['name'] === $player->full_name);

        //         if ($marketPlayer) {
        //             Log::info("Encontrado jugador inactivo en mercado para {$player->full_name} en {$team->name}");
        //         }
                
        //         if (!$marketPlayer) {
        //             $marketPlayer = $inactivePlayers->filter(function($mp) use ($player) {
        //                 $name = explode(' ', $mp['name']);
        //                 return $player->first_name === $name[0] && strpos($player->last_name, substr($name[1], 0, 3)) === 0;
        //             })->first();

        //             if (!$marketPlayer) {
        //                 $marketPlayer = $inactivePlayers->filter(function($mp) use ($player) {
        //                     $name = explode(' ', $mp['name']);
        //                     return $player->last_name === $name[0] && strpos($player->first_name, substr($name[1], 0, 3)) === 0;
        //                 })->first();
        //             }
        //         }

        //         if ($marketPlayer && !$player->market_id) {
        //             $player->update(['market_id' => $marketPlayer['id']]);
        //         } elseif (!$marketPlayer) {
        //             Log::info("No se encontró jugador para {$player->full_name} en {$team->name}");
        //         }
        //     }
        // }

        // return 'verifica';

        

        // $data = collect($request->json())->map(fn ($player) => collect($player));

        // $activePlayers = $data->where('isActive', true);
        
        // $inactivePlayers = $data->where('isActive', false);



        // dd($data);

        // return $request->json();
        
        
        // foreach (DigitalSportsTechNflEnum::getTeamIds() as $code => $marketId) {
        //     NflTeam::where('code', $code)->update(['market_id' => $marketId]);
        // }

        // return 'ok';
        
        // $request = Http::get("https://bv2-us.digitalsportstech.com/api/dfm/marketsByOu?sb=juancito&gameId=259322&statistic=Passing%2520Yards");

        // dd(empty($request->json()));

        // $teams = [];

        // foreach ($data as $game) {
        //     $teams[$game['team1'][0]['abbreviation']] = $game['team1'][0]['providers'][0]['id'];
        //     $teams[$game['team2'][0]['abbreviation']] = $game['team2'][0]['providers'][0]['id'];
        // }
        
        // return $teams;

        // $data = NflExternalService::getGame('401671813');


        // $result = Carbon::parse($data['drives']['previous'][0]['plays'][0]['wallclock']);
        // return $result;

        

        // $request = Http::withHeaders([
        //     'authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJjbGllbnRJZCI6ImU1MzVjN2MwLTgxN2YtNDc3Ni04OTkwLTU2NTU2ZjhiMTkyOCIsImNsaWVudEtleSI6IjRjRlVXNkRtd0pwelQ5TDdMckczcVJBY0FCRzVzMDRnIiwiZGV2aWNlSWQiOiJjODUxNTZjZS04NDg3LTRhNDMtYTJlYy1hYjIyMWZkZDk4NTEiLCJpc3MiOiJORkwiLCJwbGFucyI6W3sicGxhbiI6ImZyZWUiLCJleHBpcmF0aW9uRGF0ZSI6IjIwMjYtMDgtMjgiLCJzb3VyY2UiOiJORkwiLCJzdGFydERhdGUiOiIyMDI1LTA4LTI4Iiwic3RhdHVzIjoiQUNUSVZFIiwidHJpYWwiOmZhbHNlfV0sIkRpc3BsYXlOYW1lIjoiV0VCX0RFU0tUT1BfREVTS1RPUCIsIk5vdGVzIjoiIiwiZm9ybUZhY3RvciI6IkRFU0tUT1AiLCJsdXJhQXBwS2V5IjoiU1pzNTdkQkdSeGJMNzI4bFZwN0RZUSIsInBsYXRmb3JtIjoiREVTS1RPUCIsInByb2R1Y3ROYW1lIjoiV0VCIiwicm9sZXMiOlsiY29udGVudCIsImV4cGVyaWVuY2UiLCJmb290YmFsbCIsInV0aWxpdGllcyIsInRlYW1zIiwicGxheSIsImxpdmUiLCJpZGVudGl0eSIsIm5nc19zdGF0cyIsInBheW1lbnRzX2FwaSIsIm5nc190cmFja2luZyIsIm5nc19wbGF0Zm9ybSIsIm5nc19jb250ZW50IiwibmdzX2NvbWJpbmUiLCJuZ3NfYWR2YW5jZWRfc3RhdHMiLCJuZmxfcHJvIiwiZWNvbW0iLCJuZmxfaWRfYXBpIiwidXRpbGl0aWVzX2xvY2F0aW9uIiwiaWRlbnRpdHlfb2lkYyIsIm5nc19zc2UiLCJhY2NvdW50cyIsImNvbnNlbnRzIiwic3ViX3BhcnRuZXJzaGlwcyIsImNvbmN1cnJlbmN5Iiwia2V5c3RvcmUiLCJmcmVlIl0sIm5ldHdvcmtUeXBlIjoib3RoZXIiLCJjaXR5IjoibGEgb3RyYSBiYW5kYSIsImNvdW50cnlDb2RlIjoiRE8iLCJkbWFDb2RlIjoiLTEiLCJobWFUZWFtcyI6W10sInJlZ2lvbiI6IjExIiwiemlwQ29kZSI6IjIzMDAwIiwiYnJvd3NlciI6IkNocm9tZSIsImNlbGx1bGFyIjp0cnVlLCJlbnZpcm9ubWVudCI6InByb2R1Y3Rpb24iLCJleHAiOjE3NTYzNDgxMDV9.WXJZZ2NpPLBXHe8aEmojgEasyyrfHIlpdIATBsnFMLQ',
        // ])
        // ->get('https://api.nfl.com/football/v2/players');

        // return $request->json();

        // $request = Http::withHeaders([
        //     'authorization' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJjbGllbnRJZCI6ImU1MzVjN2MwLTgxN2YtNDc3Ni04OTkwLTU2NTU2ZjhiMTkyOCIsImNsaWVudEtleSI6IjRjRlVXNkRtd0pwelQ5TDdMckczcVJBY0FCRzVzMDRnIiwiZGV2aWNlSWQiOiJjODUxNTZjZS04NDg3LTRhNDMtYTJlYy1hYjIyMWZkZDk4NTEiLCJpc3MiOiJORkwiLCJwbGFucyI6W3sicGxhbiI6ImZyZWUiLCJleHBpcmF0aW9uRGF0ZSI6IjIwMjYtMDktMDEiLCJzb3VyY2UiOiJORkwiLCJzdGFydERhdGUiOiIyMDI1LTA5LTAxIiwic3RhdHVzIjoiQUNUSVZFIiwidHJpYWwiOmZhbHNlfV0sIkRpc3BsYXlOYW1lIjoiV0VCX0RFU0tUT1BfREVTS1RPUCIsIk5vdGVzIjoiIiwiZm9ybUZhY3RvciI6IkRFU0tUT1AiLCJsdXJhQXBwS2V5IjoiU1pzNTdkQkdSeGJMNzI4bFZwN0RZUSIsInBsYXRmb3JtIjoiREVTS1RPUCIsInByb2R1Y3ROYW1lIjoiV0VCIiwicm9sZXMiOlsiY29udGVudCIsImV4cGVyaWVuY2UiLCJmb290YmFsbCIsInV0aWxpdGllcyIsInRlYW1zIiwicGxheSIsImxpdmUiLCJpZGVudGl0eSIsIm5nc19zdGF0cyIsInBheW1lbnRzX2FwaSIsIm5nc190cmFja2luZyIsIm5nc19wbGF0Zm9ybSIsIm5nc19jb250ZW50IiwibmdzX2NvbWJpbmUiLCJuZ3NfYWR2YW5jZWRfc3RhdHMiLCJuZmxfcHJvIiwiZWNvbW0iLCJuZmxfaWRfYXBpIiwidXRpbGl0aWVzX2xvY2F0aW9uIiwiaWRlbnRpdHlfb2lkYyIsIm5nc19zc2UiLCJhY2NvdW50cyIsImNvbnNlbnRzIiwic3ViX3BhcnRuZXJzaGlwcyIsImNvbmN1cnJlbmN5Iiwia2V5c3RvcmUiLCJmcmVlIl0sIm5ldHdvcmtUeXBlIjoib3RoZXIiLCJjaXR5Ijoic2FuIGlzaWRybyIsImNvdW50cnlDb2RlIjoiRE8iLCJkbWFDb2RlIjoiLTEiLCJobWFUZWFtcyI6W10sInJlZ2lvbiI6IjMyIiwiemlwQ29kZSI6IjExNTAwIiwiYnJvd3NlciI6IkNocm9tZSIsImNlbGx1bGFyIjp0cnVlLCJlbnZpcm9ubWVudCI6InByb2R1Y3Rpb24iLCJleHAiOjE3NTY2OTk5MDV9.5xQVH4GHpJN4FOrBvKl_uc554FQ_xXfYoTciqx1fow8',
        // ])
        // ->get(
        //     // 'https://api.nfl.com/football/v2/players'
        //     'https://api.nfl.com/experience/v1'
        //     // 'https://api.nfl.com/football/v2/stats/live/player-statistics/7d40236a-1312-11ef-afd1-646009f18b2e'
        // );
        
        // return $request->json();

        /*

        //RUTAS
        /// ESPN 

        // NFL

        teams list
        https://site.api.espn.com/apis/site/v2/sports/football/nfl/teams/1

        team's player list
        site.api.espn.com/apis/site/v2/sports/football/nfl/teams/1/roster

        list of games by week
        https://sports.core.api.espn.com/v2/sports/football/leagues/nfl/seasons/2021/types/2/weeks/1

        game details
        https://site.api.espn.com/apis/site/v2/sports/football/nfl/summary?event=401326315



        
        // MLB
        https://site.api.espn.com/apis/site/v2/sports/baseball/mlb/teams/1 << teams list

        // NBA
        https://site.api.espn.com/apis/site/v2/sports/basketball/nba/teams/1 << teams list

        // NHL
        
        // teams list
        https://api-web.nhle.com/v1/standings/now
        
        // teams roster
        https://api-web.nhle.com/v1/roster/TOR/20252026
        
        // gameday
        https://api-web.nhle.com/v1/score/2023-11-10

        // game boxscore 
        https://api-web.nhle.com/v1/gamecenter/2023020204/boxscore





        Handicap
        Ganador Moneyline
        Total de puntos
        H1 Handicap
        H1 total de puntos
        Solo 
        Total touchdowns

        Yardas de pase

        Pases completos

        Pases de touchdowns

        intentos de pase

        Yardas de acarreo

        acarreos

        Yardas de recepcion

        recepciones

        intercepciones

        jugadores que hacen un touchdown


        // margen // 

        Yardas de pase

        pases completos

        intentos de pase

        yardas terrestres

        acarreos

        yardas de recepcion

        recepcion


        // exactos //

        pases completos 

        intentos de pase

        */

        // $request = Http::get('https://site.api.espn.com/apis/site/v2/sports/football/nfl/summary?event=401326315');

        // return $request->json();

        // {"id":"3139477","uid":"s:20~l:28~a:3139477","guid":"37d87523-280a-9d4a-0adb-22cfc6d3619c","firstName":"Patrick","lastName":"Mahomes","fullName":"Patrick Mahomes","displayName":"Patrick Mahomes","shortName":"P. Mahomes","weight":225.0,"displayWeight":"225 lbs","height":74.0,"displayHeight":"6' 2\"","age":29,"dateOfBirth":"1995-09-17T07:00Z","birthPlace":{"city":"Whitehouse","state":"TX","country":"USA"},"experience":{"years":9},"jersey":"15","active":true}
    }
}
