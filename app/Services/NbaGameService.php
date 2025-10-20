<?php

namespace App\Services;

use App\Models\NbaGame;
use App\Models\NbaInjury;
use App\Models\NbaPlayer;
use App\Repositories\NbaGameRepository;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class NbaGameService
{
    public function __construct(
        protected NbaStatsService $nbaStatsService,
        protected NbaGameRepository $nbaGameRepository,
    ){
    }

    public function list()
    {
        return NbaGame::all();
    }

    public function importGamesByDateRange(array $data)
    {
        $period = CarbonPeriod::create(Carbon::parse($data['from']), Carbon::parse($data['to']));

        foreach ($period as $date) {
            $this->nbaStatsService->importGamesByDate($date);
        }
    }

    public function getLineups(array $data = []): Collection
    {
        $lineups = $this->nbaStatsService->getTodayLineups();

        $games = collect($lineups['games'] ?? []);

        return $games->map(function (array $gamePayload) {
            $game = $this->nbaGameRepository->findByExternalId($gamePayload['gameId']);

            $inactivePlayers = collect($gamePayload['homeTeam']['players'] ?? [])
                ->merge($gamePayload['awayTeam']['players'] ?? [])
                ->filter(fn (array $playerPayload) => strcasecmp($playerPayload['rosterStatus'] ?? '', 'inactive') === 0);

            if (!$game) {
                return [
                    'external_game_id' => $gamePayload['gameId'],
                    'game' => null,
                    'injuries' => collect(),
                    'inactive_players' => $inactivePlayers->values(),
                ];
            }

            $injuries = $inactivePlayers
                ->map(function (array $playerPayload) use ($game) {
                    $player = NbaPlayer::firstWhere('external_id', $playerPayload['personId']);

                    if (!$player) {
                        return null;
                    }

                    return NbaInjury::firstOrCreate([
                        'game_id' => $game->id,
                        'player_id' => $player->id,
                    ]);
                })
                ->filter()
                ->values();

            return [
                'game' => $game->loadMissing('injuries.player'),
                'external_game_id' => $gamePayload['gameId'],
                'injuries' => $injuries,
                'inactive_players' => $inactivePlayers->values(),
            ];
        });
    }
}
