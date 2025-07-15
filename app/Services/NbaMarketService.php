<?php

namespace App\Services;

use App\Repositories\NbaGameRepository;
use App\Repositories\NbaMarketRepository;
use App\Repositories\NbaPlayerRepository;

class NbaMarketService
{
    public function __construct(
        protected DigitalSportsTechService $digitalSportsTechService,
        protected SportsnetService $sportsnetService,
        protected NbaGameRepository $nbaGameRepository,
        protected NbaMarketRepository $nbaMarketRepository,
        protected NbaPlayerRepository $nbaPlayerRepository,
    ){
    }

    public function getMatchups()
    {
        $games = NbaExternalService::getTodayLineups()->json('games');

        $homeTeamIds = $awayTeamIds = $activePlayerIds = $inactivePlayerIds = []; 

        foreach ($games as $game) {
            $homeTeamIds[] = $game['homeTeam']['teamId'];

            foreach ($game['homeTeam']['players'] as $player) {
                if ($player['rosterStatus'] === 'Active') {
                    $activePlayerIds[] = $player['personId'];
                } else {
                    $inactivePlayerIds[] = $player['personId'];
                }
            }

            $awayTeamIds[] = $game['awayTeam']['teamId'];

            foreach ($game['awayTeam']['players'] as $player) {
                if ($player['rosterStatus'] === 'Active') {
                    $activePlayerIds[] = $player['personId'];
                } else {
                    $inactivePlayerIds[] = $player['personId'];
                }
            }
        }
        
        return $this->nbaGameRepository->getDataForMarketAnalysis();
    }

    public function sync()
    {
        $this->nbaPlayerRepository->clearPlayerMarkets();
        return $this->digitalSportsTechService->syncNbaMarkets();
    }

    public function syncPlayers()
    {
        return $this->digitalSportsTechService->syncNbaPlayerMarketIds();
    }
}