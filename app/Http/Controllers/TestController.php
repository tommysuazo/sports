<?php

namespace App\Http\Controllers;

use App\Services\NbaExternalService;
use App\Services\NbaStatsService;
use App\Services\NflExternalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TestController extends Controller
{


    public function __invoke()
    {

        $data = NflExternalService::getGame('401671789');


        $result = $data['header']['competitions'][0]['competitors'];

        return $result;

        

        // $request = Http::withHeaders([
        //     'authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJjbGllbnRJZCI6ImU1MzVjN2MwLTgxN2YtNDc3Ni04OTkwLTU2NTU2ZjhiMTkyOCIsImNsaWVudEtleSI6IjRjRlVXNkRtd0pwelQ5TDdMckczcVJBY0FCRzVzMDRnIiwiZGV2aWNlSWQiOiJjODUxNTZjZS04NDg3LTRhNDMtYTJlYy1hYjIyMWZkZDk4NTEiLCJpc3MiOiJORkwiLCJwbGFucyI6W3sicGxhbiI6ImZyZWUiLCJleHBpcmF0aW9uRGF0ZSI6IjIwMjYtMDgtMjgiLCJzb3VyY2UiOiJORkwiLCJzdGFydERhdGUiOiIyMDI1LTA4LTI4Iiwic3RhdHVzIjoiQUNUSVZFIiwidHJpYWwiOmZhbHNlfV0sIkRpc3BsYXlOYW1lIjoiV0VCX0RFU0tUT1BfREVTS1RPUCIsIk5vdGVzIjoiIiwiZm9ybUZhY3RvciI6IkRFU0tUT1AiLCJsdXJhQXBwS2V5IjoiU1pzNTdkQkdSeGJMNzI4bFZwN0RZUSIsInBsYXRmb3JtIjoiREVTS1RPUCIsInByb2R1Y3ROYW1lIjoiV0VCIiwicm9sZXMiOlsiY29udGVudCIsImV4cGVyaWVuY2UiLCJmb290YmFsbCIsInV0aWxpdGllcyIsInRlYW1zIiwicGxheSIsImxpdmUiLCJpZGVudGl0eSIsIm5nc19zdGF0cyIsInBheW1lbnRzX2FwaSIsIm5nc190cmFja2luZyIsIm5nc19wbGF0Zm9ybSIsIm5nc19jb250ZW50IiwibmdzX2NvbWJpbmUiLCJuZ3NfYWR2YW5jZWRfc3RhdHMiLCJuZmxfcHJvIiwiZWNvbW0iLCJuZmxfaWRfYXBpIiwidXRpbGl0aWVzX2xvY2F0aW9uIiwiaWRlbnRpdHlfb2lkYyIsIm5nc19zc2UiLCJhY2NvdW50cyIsImNvbnNlbnRzIiwic3ViX3BhcnRuZXJzaGlwcyIsImNvbmN1cnJlbmN5Iiwia2V5c3RvcmUiLCJmcmVlIl0sIm5ldHdvcmtUeXBlIjoib3RoZXIiLCJjaXR5IjoibGEgb3RyYSBiYW5kYSIsImNvdW50cnlDb2RlIjoiRE8iLCJkbWFDb2RlIjoiLTEiLCJobWFUZWFtcyI6W10sInJlZ2lvbiI6IjExIiwiemlwQ29kZSI6IjIzMDAwIiwiYnJvd3NlciI6IkNocm9tZSIsImNlbGx1bGFyIjp0cnVlLCJlbnZpcm9ubWVudCI6InByb2R1Y3Rpb24iLCJleHAiOjE3NTYzNDgxMDV9.WXJZZ2NpPLBXHe8aEmojgEasyyrfHIlpdIATBsnFMLQ',
        // ])
        // ->get('https://api.nfl.com/football/v2/players');

        // return $request->json();

        $request = Http::withHeaders([
            'authorization' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJjbGllbnRJZCI6ImU1MzVjN2MwLTgxN2YtNDc3Ni04OTkwLTU2NTU2ZjhiMTkyOCIsImNsaWVudEtleSI6IjRjRlVXNkRtd0pwelQ5TDdMckczcVJBY0FCRzVzMDRnIiwiZGV2aWNlSWQiOiJjODUxNTZjZS04NDg3LTRhNDMtYTJlYy1hYjIyMWZkZDk4NTEiLCJpc3MiOiJORkwiLCJwbGFucyI6W3sicGxhbiI6ImZyZWUiLCJleHBpcmF0aW9uRGF0ZSI6IjIwMjYtMDktMDEiLCJzb3VyY2UiOiJORkwiLCJzdGFydERhdGUiOiIyMDI1LTA5LTAxIiwic3RhdHVzIjoiQUNUSVZFIiwidHJpYWwiOmZhbHNlfV0sIkRpc3BsYXlOYW1lIjoiV0VCX0RFU0tUT1BfREVTS1RPUCIsIk5vdGVzIjoiIiwiZm9ybUZhY3RvciI6IkRFU0tUT1AiLCJsdXJhQXBwS2V5IjoiU1pzNTdkQkdSeGJMNzI4bFZwN0RZUSIsInBsYXRmb3JtIjoiREVTS1RPUCIsInByb2R1Y3ROYW1lIjoiV0VCIiwicm9sZXMiOlsiY29udGVudCIsImV4cGVyaWVuY2UiLCJmb290YmFsbCIsInV0aWxpdGllcyIsInRlYW1zIiwicGxheSIsImxpdmUiLCJpZGVudGl0eSIsIm5nc19zdGF0cyIsInBheW1lbnRzX2FwaSIsIm5nc190cmFja2luZyIsIm5nc19wbGF0Zm9ybSIsIm5nc19jb250ZW50IiwibmdzX2NvbWJpbmUiLCJuZ3NfYWR2YW5jZWRfc3RhdHMiLCJuZmxfcHJvIiwiZWNvbW0iLCJuZmxfaWRfYXBpIiwidXRpbGl0aWVzX2xvY2F0aW9uIiwiaWRlbnRpdHlfb2lkYyIsIm5nc19zc2UiLCJhY2NvdW50cyIsImNvbnNlbnRzIiwic3ViX3BhcnRuZXJzaGlwcyIsImNvbmN1cnJlbmN5Iiwia2V5c3RvcmUiLCJmcmVlIl0sIm5ldHdvcmtUeXBlIjoib3RoZXIiLCJjaXR5Ijoic2FuIGlzaWRybyIsImNvdW50cnlDb2RlIjoiRE8iLCJkbWFDb2RlIjoiLTEiLCJobWFUZWFtcyI6W10sInJlZ2lvbiI6IjMyIiwiemlwQ29kZSI6IjExNTAwIiwiYnJvd3NlciI6IkNocm9tZSIsImNlbGx1bGFyIjp0cnVlLCJlbnZpcm9ubWVudCI6InByb2R1Y3Rpb24iLCJleHAiOjE3NTY2OTk5MDV9.5xQVH4GHpJN4FOrBvKl_uc554FQ_xXfYoTciqx1fow8',
        ])
        ->get(
            // 'https://api.nfl.com/football/v2/players'
            'https://api.nfl.com/experience/v1'
            // 'https://api.nfl.com/football/v2/stats/live/player-statistics/7d40236a-1312-11ef-afd1-646009f18b2e'
        );
        
        return $request->json();

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
        https://site.api.espn.com/apis/site/v2/sports/hockey/nhl/teams/1 << teams list

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
