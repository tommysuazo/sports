<?php

namespace App\Http\Controllers;

use App\Models\NbaPlayer;
use Illuminate\Support\Facades\Cache;

class NbaPlayerController extends Controller
{
    public function getStats(NbaPlayer $player)
    {
        $cacheKey = sprintf('nba-player-stats:%s', $player->getKey());

        return Cache::tags(['nba-player-stats'])->rememberForever($cacheKey, function () use ($player) {
            return $player->load([
                'stats' => function($query) {
                    $query->with([
                        'game' => fn($gameQuery) => $gameQuery->with(['awayTeam', 'homeTeam', 'stats', 'injuries'])
                    ])
                    ->take(16);
                }
            ]);
        });
    }

    public function getScores(NbaPlayer $player)
    {
        return $this->getStats($player);
    }
}
