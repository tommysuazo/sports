<?php

namespace App\Services;

use App\Enums\DigitalSportsTech\DigitalSportsTechLeagueEnum;
use App\Enums\DigitalSportsTech\DigitalSportsTechMarketTypeEnum;
use App\Models\NbaGame;
use App\Models\NbaPlayer;
use App\Repositories\NbaGameRepository;
use App\Repositories\NbaPlayerRepository;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class DigitalSportsTechService
{
    const BASE_URL = 'https://bv2-us.digitalsportstech.com/api/';
    
    public function __construct(
        protected NbaPlayerRepository $nbaPlayerRepository,
    ){
    }

    protected function getMarketUrl(string $marketType, string $league)
    {
        return self::BASE_URL . "dfm/gamesByOu?gameId=null&statistic={$marketType}&league={$league}";
    }

    protected function getGamePlayerMarketUrl(string $marketType, string $gameId)
    {
        return self::BASE_URL . "dfm/marketsByOu?sb=juancito&gameId={$gameId}&statistic={$marketType}";
    }

    public function getTeamPlayersUrl(string $league, string $teamShortName)
    {
        $leagueId = DigitalSportsTechLeagueEnum::getLeagueIds($league);

        $teamId = DigitalSportsTechLeagueEnum::getNbaTeamIds($teamShortName);
        
        return self::BASE_URL . "player?isActive=trueleagueId={$leagueId}&teamId={$teamId}";
    }

    public function syncNbaMarkets()
    {
        Cache::put('all', []);
        foreach (DigitalSportsTechMarketTypeEnum::all() as $statType => $marketType) {
            $marketTypesRequest = Http::get($this->getMarketUrl($marketType, 'nba'));

            if (!$marketTypesRequest->successful()) {
                throw new Exception("Failed to get nba {$statType} markets from digital sports tech");
            }
            
            Cache::put('all', array_merge(Cache::get('all'), [$statType]));
            Cache::put($statType, $marketTypesRequest->json());

            foreach ($marketTypesRequest->json('*.providers.0.id') as $marketTypeId) {
                $playerMarketTypeRequest = Http::get($this->getGamePlayerMarketUrl($marketType, $marketTypeId));

                Cache::put('all', array_merge(Cache::get('all'),[$statType . '-' . $marketTypeId]));
                Cache::put($statType . '-' . $marketTypeId, $marketTypesRequest->json());

                if (!$playerMarketTypeRequest->successful()) {
                    throw new Exception("Failed to get nba markets id {$marketTypeId} from digital sports tech");
                }

                foreach ($playerMarketTypeRequest->json('0.players') as $playerMarket) {
                    $value = $playerMarket['markets'][0]['value'];
                    NbaPlayer::where('market_id', $playerMarket['id'])->update([$statType . '_market' => $value]);
                }
            }
        }
    }
    
    public function syncNbaMarkets2()
    {
        foreach (DigitalSportsTechMarketTypeEnum::all() as $statType => $marketType) {
            $marketTypesRequest = Cache::get('marketType-' . $statType);

            foreach (Collect($marketTypesRequest)->pluck('providers.0.id') as $marketTypeId) {
                $playerMarketTypeRequest = Cache::get("marketType-{$statType}-{$marketTypeId}");

                dd(Collect($playerMarketTypeRequest)->pluck('0.players'));

                foreach (Collect($playerMarketTypeRequest)->pluck('0.players') as $playerMarket) {
                    $value = $playerMarket['markets'][0]['value'];
                    NbaPlayer::where('market_id', $playerMarket['id'])->update([$statType . '_market' => $value]);
                }
            }
        }
    }
    
    public function syncNbaPlayerMarketIds()
    {
        $allPlayers = NbaPlayer::with('team')->select('*')->selectFullname()->has('team')->get();

        foreach (DigitalSportsTechLeagueEnum::getNbaTeamIds() as $teamCode => $teamMarketId) {
            $players = $allPlayers->filter(fn($player) => $player->team->market_id == $teamMarketId);

            $response = Http::get($this->getTeamPlayersUrl('nba', $teamCode));

            foreach($response->json() as $marketPlayer) {
                $player = $players->firstWhere('full_name', $marketPlayer['name']);

                if (!$player) {
                    $name = explode(' ', $marketPlayer['name']);

                    $player = $players->filter(
                        fn($player) => $player->first_name === $name[0] && strpos($player->last_name, substr($name[1], 0, 3)) === 0
                    )->first();

                    if (!$player) {
                        $player = $players->filter(
                            fn($player) => $player->last_name === $name[1] && strpos($player->first_name, substr($name[0], 0, 3)) === 0
                        )->first();
                    }
                }

                $player?->update(['market_id' => $marketPlayer['id']]);
            }
        }
    }

    public static function getFakeGamePointsMarket()
    {
        // RECORDAR MARKETS.TYPE = 18 ES MERCADO DE TIPO A MAS Y MARKETS.TYPE = 19 ES EL MERCADO DE A MENOS
        $json = '[
            {
                "statistic": "Points",
                "type": "ou",
                "typeId": 18,
                "players": [
                    {
                        "position": {
                            "id": 9,
                            "title": "Guard-Forward"
                        },
                        "name": "Keldon Johnson",
                        "id": 223188,
                        "team": "SA",
                        "markets": [
                            {
                                "id": 31809792,
                                "condition": 3,
                                "game1Id": 234691,
                                "isActive": true,
                                "isActual": true,
                                "odds": 1.86,
                                "value": 11.5,
                                "type": 18,
                                "statistic": {
                                    "id": 2160,
                                    "title": "Points"
                                }
                            },
                            {
                                "id": 31809825,
                                "condition": 1,
                                "game1Id": 234691,
                                "isActive": true,
                                "isActual": true,
                                "odds": 1.9,
                                "value": 11.5,
                                "type": 19,
                                "statistic": {
                                    "id": 2354,
                                    "title": "Points"
                                }
                            }
                        ]
                    },
                    {
                        "position": {
                            "id": 9,
                            "title": "Guard-Forward"
                        },
                        "name": "Jalen Williams",
                        "id": 298039,
                        "team": "OKC",
                        "markets": [
                            {
                                "id": 31810164,
                                "condition": 1,
                                "game1Id": 234691,
                                "isActive": true,
                                "isActual": true,
                                "odds": 1.92,
                                "value": 19.5,
                                "type": 19,
                                "statistic": {
                                    "id": 2354,
                                    "title": "Points"
                                }
                            },
                            {
                                "id": 31810141,
                                "condition": 3,
                                "game1Id": 234691,
                                "isActive": true,
                                "isActual": true,
                                "odds": 1.84,
                                "value": 19.5,
                                "type": 18,
                                "statistic": {
                                    "id": 2160,
                                    "title": "Points"
                                }
                            }
                        ]
                    },
                    {
                        "position": {
                            "id": 8,
                            "title": "Forward-Center"
                        },
                        "name": "Chet Holmgren",
                        "id": 298041,
                        "team": "OKC",
                        "markets": [
                            {
                                "id": 31810304,
                                "condition": 3,
                                "game1Id": 234691,
                                "isActive": true,
                                "isActual": true,
                                "odds": 1.95,
                                "value": 19.5,
                                "type": 18,
                                "statistic": {
                                    "id": 2160,
                                    "title": "Points"
                                }
                            },
                            {
                                "id": 31810311,
                                "condition": 1,
                                "game1Id": 234691,
                                "isActive": true,
                                "isActual": true,
                                "odds": 1.81,
                                "value": 19.5,
                                "type": 19,
                                "statistic": {
                                    "id": 2354,
                                    "title": "Points"
                                }
                            }
                        ]
                    },
                    {
                        "position": {
                            "id": 8,
                            "title": "Forward-Center"
                        },
                        "name": "Zach Collins",
                        "id": 274743,
                        "team": "SA",
                        "markets": [
                            {
                                "id": 31810061,
                                "condition": 3,
                                "game1Id": 234691,
                                "isActive": true,
                                "isActual": true,
                                "odds": 1.97,
                                "value": 7.5,
                                "type": 18,
                                "statistic": {
                                    "id": 2160,
                                    "title": "Points"
                                }
                            },
                            {
                                "id": 31810080,
                                "condition": 1,
                                "game1Id": 234691,
                                "isActive": true,
                                "isActual": true,
                                "odds": 1.8,
                                "value": 7.5,
                                "type": 19,
                                "statistic": {
                                    "id": 2354,
                                    "title": "Points"
                                }
                            }
                        ]
                    },
                    {
                        "position": {
                            "id": 11,
                            "title": "Forward"
                        },
                        "name": "Harrison Barnes",
                        "id": 437101,
                        "team": "SA",
                        "markets": [
                            {
                                "id": 31810873,
                                "condition": 3,
                                "game1Id": 234691,
                                "isActive": true,
                                "isActual": true,
                                "odds": 1.9,
                                "value": 10.5,
                                "type": 18,
                                "statistic": {
                                    "id": 2160,
                                    "title": "Points"
                                }
                            },
                            {
                                "id": 31810877,
                                "condition": 1,
                                "game1Id": 234691,
                                "isActive": true,
                                "isActual": true,
                                "odds": 1.86,
                                "value": 10.5,
                                "type": 19,
                                "statistic": {
                                    "id": 2354,
                                    "title": "Points"
                                }
                            }
                        ]
                    },
                    {
                        "position": {
                            "id": 6,
                            "title": "Guard"
                        },
                        "name": "Chris Paul",
                        "id": 433935,
                        "team": "SA",
                        "markets": [
                            {
                                "id": 31821499,
                                "condition": 1,
                                "game1Id": 234691,
                                "isActive": true,
                                "isActual": true,
                                "odds": 1.85,
                                "value": 7.5,
                                "type": 19,
                                "statistic": {
                                    "id": 2354,
                                    "title": "Points"
                                }
                            },
                            {
                                "id": 31821498,
                                "condition": 3,
                                "game1Id": 234691,
                                "isActive": true,
                                "isActual": true,
                                "odds": 1.91,
                                "value": 7.5,
                                "type": 18,
                                "statistic": {
                                    "id": 2160,
                                    "title": "Points"
                                }
                            }
                        ]
                    },
                    {
                        "position": {
                            "id": 8,
                            "title": "Forward-Center"
                        },
                        "name": "Victor Wembanyama",
                        "id": 378861,
                        "team": "SA",
                        "markets": [
                            {
                                "id": 31825674,
                                "condition": 3,
                                "game1Id": 234691,
                                "isActive": true,
                                "isActual": true,
                                "odds": 2.05,
                                "value": 23.5,
                                "type": 18,
                                "statistic": {
                                    "id": 2160,
                                    "title": "Points"
                                }
                            },
                            {
                                "id": 31825675,
                                "condition": 1,
                                "game1Id": 234691,
                                "isActive": true,
                                "isActual": true,
                                "odds": 1.74,
                                "value": 23.5,
                                "type": 19,
                                "statistic": {
                                    "id": 2354,
                                    "title": "Points"
                                }
                            }
                        ]
                    },
                    {
                        "position": {
                            "id": 6,
                            "title": "Guard"
                        },
                        "name": "Shai Gilgeous-Alexander",
                        "id": 233275,
                        "team": "OKC",
                        "markets": [
                            {
                                "id": 31836983,
                                "condition": 1,
                                "game1Id": 234691,
                                "isActive": true,
                                "isActual": true,
                                "odds": 1.77,
                                "value": 29.5,
                                "type": 19,
                                "statistic": {
                                    "id": 2354,
                                    "title": "Points"
                                }
                            },
                            {
                                "id": 31836982,
                                "condition": 3,
                                "game1Id": 234691,
                                "isActive": true,
                                "isActual": true,
                                "odds": 2,
                                "value": 29.5,
                                "type": 18,
                                "statistic": {
                                    "id": 2160,
                                    "title": "Points"
                                }
                            }
                        ]
                    },
                    {
                        "position": {
                            "id": 11,
                            "title": "Forward"
                        },
                        "name": "Jeremy Sochan",
                        "id": 298045,
                        "team": "SA",
                        "markets": [
                            {
                                "id": 31837086,
                                "condition": 3,
                                "game1Id": 234691,
                                "isActive": true,
                                "isActual": true,
                                "odds": 1.93,
                                "value": 13.5,
                                "type": 18,
                                "statistic": {
                                    "id": 2160,
                                    "title": "Points"
                                }
                            },
                            {
                                "id": 31837087,
                                "condition": 1,
                                "game1Id": 234691,
                                "isActive": true,
                                "isActual": true,
                                "odds": 1.83,
                                "value": 13.5,
                                "type": 19,
                                "statistic": {
                                    "id": 2354,
                                    "title": "Points"
                                }
                            }
                        ]
                    },
                    {
                        "position": {
                            "id": 6,
                            "title": "Guard"
                        },
                        "name": "Stephon Castle",
                        "id": 433431,
                        "team": "SA",
                        "markets": [
                            {
                                "id": 31837268,
                                "condition": 1,
                                "game1Id": 234691,
                                "isActive": true,
                                "isActual": true,
                                "odds": 1.93,
                                "value": 6.5,
                                "type": 19,
                                "statistic": {
                                    "id": 2354,
                                    "title": "Points"
                                }
                            },
                            {
                                "id": 31837267,
                                "condition": 3,
                                "game1Id": 234691,
                                "isActive": true,
                                "isActual": true,
                                "odds": 1.83,
                                "value": 6.5,
                                "type": 18,
                                "statistic": {
                                    "id": 2160,
                                    "title": "Points"
                                }
                            }
                        ]
                    },
                    {
                        "position": {
                            "id": 6,
                            "title": "Guard"
                        },
                        "name": "Luguentz Dort",
                        "id": 233268,
                        "team": "OKC",
                        "markets": [
                            {
                                "id": 31837796,
                                "condition": 3,
                                "game1Id": 234691,
                                "isActive": true,
                                "isActual": true,
                                "odds": 1.81,
                                "value": 8.5,
                                "type": 18,
                                "statistic": {
                                    "id": 2160,
                                    "title": "Points"
                                }
                            },
                            {
                                "id": 31837797,
                                "condition": 1,
                                "game1Id": 234691,
                                "isActive": true,
                                "isActual": true,
                                "odds": 1.95,
                                "value": 8.5,
                                "type": 19,
                                "statistic": {
                                    "id": 2354,
                                    "title": "Points"
                                }
                            }
                        ]
                    },
                    {
                        "position": {
                            "id": 11,
                            "title": "Forward"
                        },
                        "name": "Julian Champagnie",
                        "id": 369082,
                        "team": "SA",
                        "markets": [
                            {
                                "id": 31838175,
                                "condition": 1,
                                "game1Id": 234691,
                                "isActive": true,
                                "isActual": true,
                                "odds": 1.83,
                                "value": 9.5,
                                "type": 19,
                                "statistic": {
                                    "id": 2354,
                                    "title": "Points"
                                }
                            },
                            {
                                "id": 31838176,
                                "condition": 3,
                                "game1Id": 234691,
                                "isActive": true,
                                "isActual": true,
                                "odds": 1.93,
                                "value": 9.5,
                                "type": 18,
                                "statistic": {
                                    "id": 2160,
                                    "title": "Points"
                                }
                            }
                        ]
                    }
                ]
            }
        ]';

        return json_decode($json);
        
    }

}