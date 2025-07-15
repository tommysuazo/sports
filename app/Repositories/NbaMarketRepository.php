<?php

namespace App\Repositories;

use App\Models\NbaTeam;

class NbaMarketRepository
{
    public function list()
    {
        // $playersQuery = NbaPlayer::query();

        // $players = (clone $playersQuery)->hasMarkets()->toBase()->get();

        // $scores = NbaPlayerScore::whereIn('player_id', $players->pluck('id'))->toBase()->get();

        // $teams = NbaTeam::whereIn('id', $players->pluck('team_id')->unique())->toBase()->get();

        // foreach ($teams as &$team) {
        //     $team->players = $players->where('team_id', $team->id);
        //     foreach ($team->players as &$player) {
        //         $player->scores = $scores->where('player_id', $player->id);
        //     }
        // }

        // return $teams;

        return NbaTeam::with([
            'players' => function($wq) {
                $wq->with([
                    'scores' => fn($queryScore) => $queryScore->take(16),
                    'scores' => fn($queryScore) => $queryScore->take(16),
                    'scores' => fn($queryScore) => $queryScore->take(16),
                    
                ])->hasMarkets();
            }
        ])
            ->whereHas('players', fn($query) => $query->hasMarkets())->get();
    }

    public function show() {}

    
}
