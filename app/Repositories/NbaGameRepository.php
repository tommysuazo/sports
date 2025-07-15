<?php

namespace App\Repositories;

use App\Enums\Games\NbaGameStatus;
use App\Models\NbaGame;
use App\Models\NbaTeamScore;
use App\Models\NbaTeam;
use Carbon\Carbon;

class NbaGameRepository 
{
    public function list(array $filters)
    {
        
    }

    public function getDataForMarketAnalysis()
    {
        return NbaGame::with([
            'awayTeam' => function ($query) {
                $query->with([
                    'players' => function ($queryPlayer) {
                        $queryPlayer->hasMarkets()
                            ->with([
                                'scores' => fn($queryScore) => $queryScore->orderByDesc('nba_player_scores.id')->take(5),
                                'awayScores' => fn($queryScore) => $queryScore->orderByDesc('nba_player_scores.id')->take(5),
                            ]);
                    },
                ]);
            },
            'homeTeam' => function ($query) {
                $query->with([
                    'players' => function ($queryPlayer) {
                        $queryPlayer->hasMarkets()
                            ->with([
                                'scores' => fn($queryScore) => $queryScore->orderByDesc('nba_player_scores.id')->take(5),
                                'homeScores' => fn($queryScore) => $queryScore->orderByDesc('nba_player_scores.id')->take(5),
                            ]);
                    },
                ]);
            },
        ])
        ->whereDate('started_at', now()->toDateString())
        ->skip(2)->take(1)
        ->get();
    }

    public function findByExternalId(string $externalId): NbaGame|null
    {
        return NbaGame::firstWhere('external_id', $externalId);
    }

    public function create(array $data, NbaTeam $awayTeam, NbaTeam $homeTeam): NbaGame
    {
        return NbaGame::create([
            'external_id' => $data['external_id'] ?? null,
            'away_team_id' => $awayTeam->id,
            'home_team_id' => $homeTeam->id,
            'started_at' => $data['started_at'],
        ]);
    }
}