<?php

namespace App\Services;

use App\Models\NbaGame;
use App\Models\NbaInjury;
use App\Models\NbaPlayer;
use Illuminate\Support\Collection;

class NbaInjuryService
{
    public function __construct(
        protected NbaExternalService $NbaExternalService,
    ) {
    }

    /**
     * Sincroniza las lesiones del día actual en base a las alineaciones oficiales.
     */
    public function syncTodayInjuries(): array
    {
        $lineups = $this->NbaExternalService->getTodayLineups();
        $gamesData = data_get($lineups, 'games', []);

        $processedGames = 0;
        $createdInjuries = 0;
        $removedInjuries = 0;
        $missingGames = [];
        $missingPlayers = [];

        foreach ($gamesData as $gameData) {
            $game = NbaGame::firstWhere('external_id', $gameData['gameId']);

            if (!$game) {
                $missingGames[] = $gameData['gameId'];
                continue;
            }

            $processedGames++;

            $injuredExternalIds = $this->collectInjuredPlayers($gameData);

            if ($injuredExternalIds->isEmpty()) {
                $removedInjuries += NbaInjury::where('game_id', $game->id)->delete();
                continue;
            }

            $players = NbaPlayer::whereIn('external_id', $injuredExternalIds)->get()->keyBy('external_id');

            $missingForGame = $injuredExternalIds
                ->diff($players->keys())
                ->values()
                ->all();

            if (!empty($missingForGame)) {
                $missingPlayers[$gameData['gameId']] = $missingForGame;
            }

            $injuredPlayerIds = $players->pluck('id')->all();

            $removedInjuries += NbaInjury::where('game_id', $game->id)
                ->whereNotIn('player_id', $injuredPlayerIds)
                ->delete();

            foreach ($injuredPlayerIds as $playerId) {
                $injury = NbaInjury::firstOrCreate([
                    'game_id' => $game->id,
                    'player_id' => $playerId,
                ]);

                if ($injury->wasRecentlyCreated) {
                    $createdInjuries++;
                }
            }
        }

        return [
            'games_processed' => $processedGames,
            'injuries_created' => $createdInjuries,
            'injuries_removed' => $removedInjuries,
            'missing_games' => $missingGames,
            'missing_players' => $missingPlayers,
        ];
    }

    /**
     * Obtiene los IDs externos de los jugadores marcados como inactivos.
     */
    protected function collectInjuredPlayers(array $gameData): Collection
    {
        return collect([
                data_get($gameData, 'homeTeam.players', []),
                data_get($gameData, 'awayTeam.players', []),
            ])
            ->flatten(1)
            ->filter(fn (array $player) => strcasecmp($player['rosterStatus'] ?? '', 'Active') !== 0)
            ->pluck('personId');
    }
}
