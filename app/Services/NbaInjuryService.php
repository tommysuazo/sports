<?php

namespace App\Services;

use App\Models\NbaGame;
use App\Models\NbaInjury;
use App\Models\NbaPlayer;
use App\Models\NbaTeam;
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
        $missingTeams = [];

        foreach ($gamesData as $gameData) {
            $game = NbaGame::firstWhere('external_id', $gameData['gameId']);

            if (!$game) {
                $missingGames[] = $gameData['gameId'];
                continue;
            }

            $processedGames++;

            $injuredPayloads = $this->collectInjuredPlayers($gameData);

            if ($injuredPayloads->isEmpty()) {
                $removedInjuries += NbaInjury::where('game_id', $game->id)->delete();
                continue;
            }

            $injuredExternalIds = $injuredPayloads
                ->pluck('player_external_id')
                ->filter()
                ->unique()
                ->values();

            $teamExternalIds = $injuredPayloads
                ->pluck('team_external_id')
                ->filter()
                ->unique()
                ->values();

            $players = NbaPlayer::whereIn('external_id', $injuredExternalIds)->get()->keyBy('external_id');
            $teams = $teamExternalIds->isNotEmpty()
                ? NbaTeam::whereIn('external_id', $teamExternalIds)->get()->keyBy('external_id')
                : collect();

            $missingForGame = $injuredExternalIds
                ->diff($players->keys())
                ->values()
                ->all();

            if (!empty($missingForGame)) {
                $missingPlayers[$gameData['gameId']] = $missingForGame;
            }

            $injuryRecords = collect();
            $missingTeamForGame = [];

            foreach ($injuredPayloads as $payload) {
                $player = $players->get($payload['player_external_id']);

                if (!$player) {
                    continue;
                }

                $teamId = $player->team_id;

                if (!$teamId && $payload['team_external_id']) {
                    $teamId = optional($teams->get($payload['team_external_id']))?->id;
                }

                if (!$teamId) {
                    $missingTeamForGame[] = [
                        'player_external_id' => $payload['player_external_id'],
                        'team_external_id' => $payload['team_external_id'],
                    ];
                    continue;
                }

                $injuryRecords->push([
                    'player_id' => $player->id,
                    'team_id' => $teamId,
                ]);
            }

            if (!empty($missingTeamForGame)) {
                $missingTeams[$gameData['gameId']] = $missingTeamForGame;
            }

            $injuredPlayerIds = $injuryRecords
                ->pluck('player_id')
                ->unique()
                ->values()
                ->all();

            $removedInjuries += NbaInjury::where('game_id', $game->id)
                ->whereNotIn('player_id', $injuredPlayerIds)
                ->delete();

            foreach ($injuryRecords->unique(fn (array $record) => "{$record['player_id']}-{$record['team_id']}") as $record) {
                $injury = NbaInjury::firstOrCreate([
                    'game_id' => $game->id,
                    'player_id' => $record['player_id'],
                    'team_id' => $record['team_id'],
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
            'missing_teams' => $missingTeams,
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
            ->map(fn (array $player) => [
                'player_external_id' => $player['personId'] ?? null,
                'team_external_id' => $player['teamId'] ?? null,
            ])
            ->filter(fn (array $payload) => !is_null($payload['player_external_id']))
            ->values();
    }
}
