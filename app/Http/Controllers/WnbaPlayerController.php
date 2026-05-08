<?php

namespace App\Http\Controllers;

use App\Models\WnbaPlayer;

class WnbaPlayerController extends Controller
{
    public function getStats(WnbaPlayer $player)
    {
        return $player->load([
            'stats' => function ($query) {
                $query->with([
                    'game' => fn ($gameQuery) => $gameQuery->with(['awayTeam', 'homeTeam', 'stats']),
                ])
                    ->orderByDesc('wnba_player_stats.id')
                    ->take(16);
            },
        ]);
    }

    public function getScores(WnbaPlayer $player)
    {
        return $this->getStats($player);
    }
}
