<?php

namespace App\Http\Controllers;

use App\Models\NflPlayer;
use App\Services\NflMarketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NflPlayerController extends Controller
{
    public function __construct(
        protected NflMarketService $nflMarketService,
    ) {
    }

    public function index()
    {
        return $this->nflMarketService->syncMarkets();
    }

    public function getStats(NflPlayer $player)
    {
        $cacheKey = sprintf('player-stats:%s', $player->getKey());

        return Cache::tags(['player-stats'])->rememberForever($cacheKey, function () use ($player) {
            return $player->load([
                'stats' => fn($query) => $query->with([
                    'game' => fn($q) => $q->with(['homeTeam:id,code', 'awayTeam:id,code']),
                ])->orderByDesc('nfl_player_stats.id'),
            ]);
        });
    }
}
