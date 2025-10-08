<?php

namespace App\Http\Controllers;

use App\Models\NhlPlayer;
use Illuminate\Support\Facades\Cache;

class NhlPlayerController extends Controller
{
    public function getStats(NhlPlayer $player)
    {
        $cacheKey = sprintf('nhl-player-stats:%s', $player->getKey());

        return Cache::tags(['nhl-player-stats'])->rememberForever($cacheKey, function () use ($player) {
            return $player->load([
                'stats' => fn ($query) => $query
                    ->with([
                        'game' => fn ($gameQuery) => $gameQuery->with(['homeTeam:id,code', 'awayTeam:id,code']),
                    ])
                    ->orderByDesc('nhl_player_stats.id'),
            ]);
        });
    }
}
