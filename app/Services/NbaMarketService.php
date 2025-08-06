<?php

namespace App\Services;

use App\Repositories\NbaGameRepository;
use App\Repositories\NbaMarketRepository;
use App\Repositories\NbaPlayerRepository;
use App\Repositories\NbaTeamRepository;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class NbaMarketService
{
    public function __construct(
        protected DigitalSportsTechService $digitalSportsTechService,
        protected SportsnetService $sportsnetService,
        protected NbaGameRepository $nbaGameRepository,
        protected NbaMarketRepository $nbaMarketRepository,
        protected NbaPlayerRepository $nbaPlayerRepository,
        protected NbaTeamRepository $nbaTeamRepository,
        protected NbaExternalService $nbaExternalService
    ){
    }

    public function getMarkets()
    {
        return $this->digitalSportsTechService->getNbaPlayerMarkets();
    }

    public function getMatchups()
    {
        $cacheKey = now()->toDateString();

        $matchups = false; 
        // $matchups = Cache::tags('matchups')->get($cacheKey);

        if ($matchups) {
            return $matchups;
        } else {
            // $lineups = collect($this->nbaExternalService->getTodayLineups());

            // $games = $lineups->map(fn($game) => [
            //     'homeTeamId' => $game->homeTeam->teamId,
            //     'awayTeamId' => $game->awayTeam->teamId,
            // ]);

            $games = collect([
                [
                    'awayTeamId' => 1611661324,
                    'homeTeamId' => 1611661319,
                ],
                // [
                //     'awayTeamId' => 1611661313,
                //     'homeTeamId' => 1611661323,
                // ],
                // [
                //     'awayTeamId' => 1611661331,
                //     'homeTeamId' => 1611661329,
                // ],
                // [
                //     'awayTeamId' => 1611661325,
                //     'homeTeamId' => 1611661321,
                // ],
                // [
                //     'awayTeamId' => 1611661320,
                //     'homeTeamId' => 1611661328,
                // ],
            ]);

            $awayTeamIds = $games->map(fn($game) => $game['awayTeamId'])->toArray();
            $awayTeams = $this->nbaTeamRepository->getTeamsDataForMatchups($awayTeamIds, false);

            $homeTeamIds = $games->map(fn($game) => $game['homeTeamId'])->toArray();
            $homeTeams = $this->nbaTeamRepository->getTeamsDataForMatchups($homeTeamIds);

            $matchups = $games->mapWithKeys(function ($game) use ($awayTeams, $homeTeams) {
                $homeTeam = $homeTeams->firstWhere('external_id', $game['homeTeamId']);
                
                return [
                    $homeTeam->market_id => [
                        'away_team' => $awayTeams->firstWhere('external_id', $game['awayTeamId']),
                        'home_team' => $homeTeam,
                    ]
                ];
            });
        }

        return $matchups;
    }

    public function sync()
    {
        return $this->digitalSportsTechService->syncNbaMarkets();
    }

    public function syncPlayers()
    {
        return $this->digitalSportsTechService->syncNbaPlayerMarketIds();
    }
    
    public function syncWnbaPlayers()
    {
        return $this->digitalSportsTechService->syncWnbaPlayerMarketIds();
    }

    public function getLineups()
    {
        return json_decode('
            {
                "games": [
                    {
                        "gameId": "0022401128",
                        "gameStatus": 1,
                        "gameStatusText": "3:00 pm ET",
                        "homeTeam": {
                            "teamId": 1610612737,
                            "teamAbbreviation": "ATL",
                            "players": [
                                {
                                    "personId": 1630700,
                                    "teamId": 1610612737,
                                    "firstName": "Dyson",
                                    "lastName": "Daniels",
                                    "playerName": "Dyson Daniels",
                                    "lineupStatus": "Confirmed",
                                    "position": "SG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1642258,
                                    "teamId": 1610612737,
                                    "firstName": "Zaccharie",
                                    "lastName": "Risacher",
                                    "playerName": "Zaccharie Risacher",
                                    "lineupStatus": "Confirmed",
                                    "position": "SF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1629027,
                                    "teamId": 1610612737,
                                    "firstName": "Trae",
                                    "lastName": "Young",
                                    "playerName": "Trae Young",
                                    "lineupStatus": "Confirmed",
                                    "position": "PG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1631243,
                                    "teamId": 1610612737,
                                    "firstName": "Mouhamed",
                                    "lastName": "Gueye",
                                    "playerName": "Mouhamed Gueye",
                                    "lineupStatus": "Confirmed",
                                    "position": "PF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1630168,
                                    "teamId": 1610612737,
                                    "firstName": "Onyeka",
                                    "lastName": "Okongwu",
                                    "playerName": "Onyeka Okongwu",
                                    "lineupStatus": "Confirmed",
                                    "position": "C",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1627747,
                                    "teamId": 1610612737,
                                    "firstName": "Caris",
                                    "lastName": "LeVert",
                                    "playerName": "Caris LeVert",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1627777,
                                    "teamId": 1610612737,
                                    "firstName": "Georges",
                                    "lastName": "Niang",
                                    "playerName": "Georges Niang",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1629611,
                                    "teamId": 1610612737,
                                    "firstName": "Terance",
                                    "lastName": "Mann",
                                    "playerName": "Terance Mann",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1629726,
                                    "teamId": 1610612737,
                                    "firstName": "Garrison",
                                    "lastName": "Mathews",
                                    "playerName": "Garrison Mathews",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1630249,
                                    "teamId": 1610612737,
                                    "firstName": "Vít",
                                    "lastName": "Krejčí",
                                    "playerName": "Vít Krejčí",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1630811,
                                    "teamId": 1610612737,
                                    "firstName": "Keaton",
                                    "lastName": "Wallace",
                                    "playerName": "Keaton Wallace",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1631230,
                                    "teamId": 1610612737,
                                    "firstName": "Dominick",
                                    "lastName": "Barlow",
                                    "playerName": "Dominick Barlow",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1631342,
                                    "teamId": 1610612737,
                                    "firstName": "Daeqwon",
                                    "lastName": "Plowden",
                                    "playerName": "Daeqwon Plowden",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 203991,
                                    "teamId": 1610612737,
                                    "firstName": "Clint",
                                    "lastName": "Capela",
                                    "playerName": "Clint Capela",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Inactive",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1626204,
                                    "teamId": 1610612737,
                                    "firstName": "Larry",
                                    "lastName": "Nance Jr.",
                                    "playerName": "Larry Nance Jr.",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Inactive",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1630552,
                                    "teamId": 1610612737,
                                    "firstName": "Jalen",
                                    "lastName": "Johnson",
                                    "playerName": "Jalen Johnson",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Inactive",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1631210,
                                    "teamId": 1610612737,
                                    "firstName": "Jacob",
                                    "lastName": "Toppin",
                                    "playerName": "Jacob Toppin",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Inactive",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1641723,
                                    "teamId": 1610612737,
                                    "firstName": "Kobe",
                                    "lastName": "Bufkin",
                                    "playerName": "Kobe Bufkin",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Inactive",
                                    "timestamp": "2025-04-05T14:34:00"
                                }
                            ]
                        },
                        "awayTeam": {
                            "teamId": 1610612752,
                            "teamAbbreviation": "NYK",
                            "players": [
                                {
                                    "personId": 1628969,
                                    "teamId": 1610612752,
                                    "firstName": "Mikal",
                                    "lastName": "Bridges",
                                    "playerName": "Mikal Bridges",
                                    "lineupStatus": "Confirmed",
                                    "position": "SG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1628404,
                                    "teamId": 1610612752,
                                    "firstName": "Josh",
                                    "lastName": "Hart",
                                    "playerName": "Josh Hart",
                                    "lineupStatus": "Confirmed",
                                    "position": "SF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1626153,
                                    "teamId": 1610612752,
                                    "firstName": "Delon",
                                    "lastName": "Wright",
                                    "playerName": "Delon Wright",
                                    "lineupStatus": "Confirmed",
                                    "position": "PG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1628384,
                                    "teamId": 1610612752,
                                    "firstName": "OG",
                                    "lastName": "Anunoby",
                                    "playerName": "OG Anunoby",
                                    "lineupStatus": "Confirmed",
                                    "position": "PF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1626157,
                                    "teamId": 1610612752,
                                    "firstName": "Karl-Anthony",
                                    "lastName": "Towns",
                                    "playerName": "Karl-Anthony Towns",
                                    "lineupStatus": "Confirmed",
                                    "position": "C",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 200782,
                                    "teamId": 1610612752,
                                    "firstName": "P.J.",
                                    "lastName": "Tucker",
                                    "playerName": "P.J. Tucker",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1626166,
                                    "teamId": 1610612752,
                                    "firstName": "Cameron",
                                    "lastName": "Payne",
                                    "playerName": "Cameron Payne",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1628973,
                                    "teamId": 1610612752,
                                    "firstName": "Jalen",
                                    "lastName": "Brunson",
                                    "playerName": "Jalen Brunson",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1629013,
                                    "teamId": 1610612752,
                                    "firstName": "Landry",
                                    "lastName": "Shamet",
                                    "playerName": "Landry Shamet",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1630173,
                                    "teamId": 1610612752,
                                    "firstName": "Precious",
                                    "lastName": "Achiuwa",
                                    "playerName": "Precious Achiuwa",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1630699,
                                    "teamId": 1610612752,
                                    "firstName": "MarJon",
                                    "lastName": "Beauchamp",
                                    "playerName": "MarJon Beauchamp",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1641755,
                                    "teamId": 1610612752,
                                    "firstName": "Kevin",
                                    "lastName": "McCullar Jr.",
                                    "playerName": "Kevin McCullar Jr.",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1641817,
                                    "teamId": 1610612752,
                                    "firstName": "Anton",
                                    "lastName": "Watson",
                                    "playerName": "Anton Watson",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1642278,
                                    "teamId": 1610612752,
                                    "firstName": "Tyler",
                                    "lastName": "Kolek",
                                    "playerName": "Tyler Kolek",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1642359,
                                    "teamId": 1610612752,
                                    "firstName": "Pacôme",
                                    "lastName": "Dadiet",
                                    "playerName": "Pacôme Dadiet",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1629011,
                                    "teamId": 1610612752,
                                    "firstName": "Mitchell",
                                    "lastName": "Robinson",
                                    "playerName": "Mitchell Robinson",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Inactive",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1630540,
                                    "teamId": 1610612752,
                                    "firstName": "Miles",
                                    "lastName": "McBride",
                                    "playerName": "Miles McBride",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Inactive",
                                    "timestamp": "2025-04-05T14:34:00"
                                },
                                {
                                    "personId": 1630574,
                                    "teamId": 1610612752,
                                    "firstName": "Ariel",
                                    "lastName": "Hukporti",
                                    "playerName": "Ariel Hukporti",
                                    "lineupStatus": "Confirmed",
                                    "position": "",
                                    "rosterStatus": "Inactive",
                                    "timestamp": "2025-04-05T14:34:00"
                                }
                            ]
                        }
                    },
                    {
                        "gameId": "0022401129",
                        "gameStatus": 1,
                        "gameStatusText": "7:00 pm ET",
                        "homeTeam": {
                            "teamId": 1610612765,
                            "teamAbbreviation": "DET",
                            "players": [
                                {
                                    "personId": 203501,
                                    "teamId": 1610612765,
                                    "firstName": "Tim",
                                    "lastName": "Hardaway Jr.",
                                    "playerName": "Tim Hardaway Jr.",
                                    "lineupStatus": "Expected",
                                    "position": "SG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1641709,
                                    "teamId": 1610612765,
                                    "firstName": "Ausar",
                                    "lastName": "Thompson",
                                    "playerName": "Ausar Thompson",
                                    "lineupStatus": "Expected",
                                    "position": "SF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1630595,
                                    "teamId": 1610612765,
                                    "firstName": "Cade",
                                    "lastName": "Cunningham",
                                    "playerName": "Cade Cunningham",
                                    "lineupStatus": "Expected",
                                    "position": "PG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 202699,
                                    "teamId": 1610612765,
                                    "firstName": "Tobias",
                                    "lastName": "Harris",
                                    "playerName": "Tobias Harris",
                                    "lineupStatus": "Expected",
                                    "position": "PF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1631105,
                                    "teamId": 1610612765,
                                    "firstName": "Jalen",
                                    "lastName": "Duren",
                                    "playerName": "Jalen Duren",
                                    "lineupStatus": "Expected",
                                    "position": "C",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                }
                            ]
                        },
                        "awayTeam": {
                            "teamId": 1610612763,
                            "teamAbbreviation": "MEM",
                            "players": [
                                {
                                    "personId": 1630217,
                                    "teamId": 1610612763,
                                    "firstName": "Desmond",
                                    "lastName": "Bane",
                                    "playerName": "Desmond Bane",
                                    "lineupStatus": "Expected",
                                    "position": "SG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1642377,
                                    "teamId": 1610612763,
                                    "firstName": "Jaylen",
                                    "lastName": "Wells",
                                    "playerName": "Jaylen Wells",
                                    "lineupStatus": "Expected",
                                    "position": "SF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1629630,
                                    "teamId": 1610612763,
                                    "firstName": "Ja",
                                    "lastName": "Morant",
                                    "playerName": "Ja Morant",
                                    "lineupStatus": "Expected",
                                    "position": "PG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1628991,
                                    "teamId": 1610612763,
                                    "firstName": "Jaren",
                                    "lastName": "Jackson Jr.",
                                    "playerName": "Jaren Jackson Jr.",
                                    "lineupStatus": "Expected",
                                    "position": "PF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1641744,
                                    "teamId": 1610612763,
                                    "firstName": "Zach",
                                    "lastName": "Edey",
                                    "playerName": "Zach Edey",
                                    "lineupStatus": "Expected",
                                    "position": "C",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                }
                            ]
                        }
                    },
                    {
                        "gameId": "0022401130",
                        "gameStatus": 1,
                        "gameStatusText": "7:00 pm ET",
                        "homeTeam": {
                            "teamId": 1610612755,
                            "teamAbbreviation": "PHI",
                            "players": [
                                {
                                    "personId": 1629656,
                                    "teamId": 1610612755,
                                    "firstName": "Quentin",
                                    "lastName": "Grimes",
                                    "playerName": "Quentin Grimes",
                                    "lineupStatus": "Expected",
                                    "position": "SG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1642348,
                                    "teamId": 1610612755,
                                    "firstName": "Justin",
                                    "lastName": "Edwards",
                                    "playerName": "Justin Edwards",
                                    "lineupStatus": "Expected",
                                    "position": "SF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1630215,
                                    "teamId": 1610612755,
                                    "firstName": "Jared",
                                    "lastName": "Butler",
                                    "playerName": "Jared Butler",
                                    "lineupStatus": "Expected",
                                    "position": "PG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1627824,
                                    "teamId": 1610612755,
                                    "firstName": "Guerschon",
                                    "lastName": "Yabusele",
                                    "playerName": "Guerschon Yabusele",
                                    "lineupStatus": "Expected",
                                    "position": "PF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1641737,
                                    "teamId": 1610612755,
                                    "firstName": "Adem",
                                    "lastName": "Bona",
                                    "playerName": "Adem Bona",
                                    "lineupStatus": "Expected",
                                    "position": "C",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                }
                            ]
                        },
                        "awayTeam": {
                            "teamId": 1610612750,
                            "teamAbbreviation": "MIN",
                            "players": [
                                {
                                    "personId": 1630162,
                                    "teamId": 1610612750,
                                    "firstName": "Anthony",
                                    "lastName": "Edwards",
                                    "playerName": "Anthony Edwards",
                                    "lineupStatus": "Expected",
                                    "position": "SG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1630183,
                                    "teamId": 1610612750,
                                    "firstName": "Jaden",
                                    "lastName": "McDaniels",
                                    "playerName": "Jaden McDaniels",
                                    "lineupStatus": "Expected",
                                    "position": "SF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 201144,
                                    "teamId": 1610612750,
                                    "firstName": "Mike",
                                    "lastName": "Conley",
                                    "playerName": "Mike Conley",
                                    "lineupStatus": "Expected",
                                    "position": "PG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 203944,
                                    "teamId": 1610612750,
                                    "firstName": "Julius",
                                    "lastName": "Randle",
                                    "playerName": "Julius Randle",
                                    "lineupStatus": "Expected",
                                    "position": "PF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 203497,
                                    "teamId": 1610612750,
                                    "firstName": "Rudy",
                                    "lastName": "Gobert",
                                    "playerName": "Rudy Gobert",
                                    "lineupStatus": "Expected",
                                    "position": "C",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                }
                            ]
                        }
                    },
                    {
                        "gameId": "0022401131",
                        "gameStatus": 1,
                        "gameStatusText": "8:00 pm ET",
                        "homeTeam": {
                            "teamId": 1610612748,
                            "teamAbbreviation": "MIA",
                            "players": [
                                {
                                    "personId": 202692,
                                    "teamId": 1610612748,
                                    "firstName": "Alec",
                                    "lastName": "Burks",
                                    "playerName": "Alec Burks",
                                    "lineupStatus": "Expected",
                                    "position": "SG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1641796,
                                    "teamId": 1610612748,
                                    "firstName": "Pelle",
                                    "lastName": "Larsson",
                                    "playerName": "Pelle Larsson",
                                    "lineupStatus": "Expected",
                                    "position": "SF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1629639,
                                    "teamId": 1610612748,
                                    "firstName": "Tyler",
                                    "lastName": "Herro",
                                    "playerName": "Tyler Herro",
                                    "lineupStatus": "Expected",
                                    "position": "PG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1628389,
                                    "teamId": 1610612748,
                                    "firstName": "Bam",
                                    "lastName": "Adebayo",
                                    "playerName": "Bam Adebayo",
                                    "lineupStatus": "Expected",
                                    "position": "PF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1642276,
                                    "teamId": 1610612748,
                                    "firstName": "Kel\'el",
                                    "lastName": "Ware",
                                    "playerName": "Kel\'el Ware",
                                    "lineupStatus": "Expected",
                                    "position": "C",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                }
                            ]
                        },
                        "awayTeam": {
                            "teamId": 1610612749,
                            "teamAbbreviation": "MIL",
                            "players": [
                                {
                                    "personId": 1627752,
                                    "teamId": 1610612749,
                                    "firstName": "Taurean",
                                    "lastName": "Prince",
                                    "playerName": "Taurean Prince",
                                    "lineupStatus": "Expected",
                                    "position": "SG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1628398,
                                    "teamId": 1610612749,
                                    "firstName": "Kyle",
                                    "lastName": "Kuzma",
                                    "playerName": "Kyle Kuzma",
                                    "lineupStatus": "Expected",
                                    "position": "SF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1631157,
                                    "teamId": 1610612749,
                                    "firstName": "Ryan",
                                    "lastName": "Rollins",
                                    "playerName": "Ryan Rollins",
                                    "lineupStatus": "Expected",
                                    "position": "PG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 203507,
                                    "teamId": 1610612749,
                                    "firstName": "Giannis",
                                    "lastName": "Antetokounmpo",
                                    "playerName": "Giannis Antetokounmpo",
                                    "lineupStatus": "Expected",
                                    "position": "PF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 201572,
                                    "teamId": 1610612749,
                                    "firstName": "Brook",
                                    "lastName": "Lopez",
                                    "playerName": "Brook Lopez",
                                    "lineupStatus": "Expected",
                                    "position": "C",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                }
                            ]
                        }
                    },
                    {
                        "gameId": "0022401132",
                        "gameStatus": 1,
                        "gameStatusText": "10:30 pm ET",
                        "homeTeam": {
                            "teamId": 1610612746,
                            "teamAbbreviation": "LAC",
                            "players": [
                                {
                                    "personId": 1627739,
                                    "teamId": 1610612746,
                                    "firstName": "Kris",
                                    "lastName": "Dunn",
                                    "playerName": "Kris Dunn",
                                    "lineupStatus": "Expected",
                                    "position": "SG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1626181,
                                    "teamId": 1610612746,
                                    "firstName": "Norman",
                                    "lastName": "Powell",
                                    "playerName": "Norman Powell",
                                    "lineupStatus": "Expected",
                                    "position": "SF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 201935,
                                    "teamId": 1610612746,
                                    "firstName": "James",
                                    "lastName": "Harden",
                                    "playerName": "James Harden",
                                    "lineupStatus": "Expected",
                                    "position": "PG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1627884,
                                    "teamId": 1610612746,
                                    "firstName": "Derrick",
                                    "lastName": "Jones Jr.",
                                    "playerName": "Derrick Jones Jr.",
                                    "lineupStatus": "Expected",
                                    "position": "PF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1627826,
                                    "teamId": 1610612746,
                                    "firstName": "Ivica",
                                    "lastName": "Zubac",
                                    "playerName": "Ivica Zubac",
                                    "lineupStatus": "Expected",
                                    "position": "C",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                }
                            ]
                        },
                        "awayTeam": {
                            "teamId": 1610612742,
                            "teamAbbreviation": "DAL",
                            "players": [
                                {
                                    "personId": 202691,
                                    "teamId": 1610612742,
                                    "firstName": "Klay",
                                    "lastName": "Thompson",
                                    "playerName": "Klay Thompson",
                                    "lineupStatus": "Expected",
                                    "position": "SG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1629023,
                                    "teamId": 1610612742,
                                    "firstName": "P.J.",
                                    "lastName": "Washington",
                                    "playerName": "P.J. Washington",
                                    "lineupStatus": "Expected",
                                    "position": "SF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 203915,
                                    "teamId": 1610612742,
                                    "firstName": "Spencer",
                                    "lastName": "Dinwiddie",
                                    "playerName": "Spencer Dinwiddie",
                                    "lineupStatus": "Expected",
                                    "position": "PG",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 203076,
                                    "teamId": 1610612742,
                                    "firstName": "Anthony",
                                    "lastName": "Davis",
                                    "playerName": "Anthony Davis",
                                    "lineupStatus": "Expected",
                                    "position": "PF",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                },
                                {
                                    "personId": 1641726,
                                    "teamId": 1610612742,
                                    "firstName": "Dereck",
                                    "lastName": "Lively II",
                                    "playerName": "Dereck Lively II",
                                    "lineupStatus": "Expected",
                                    "position": "C",
                                    "rosterStatus": "Active",
                                    "timestamp": "2025-04-05T14:47:34"
                                }
                            ]
                        }
                    }
                ]
            }
        ');
    }
}