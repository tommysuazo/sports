<?php

namespace App\Repositories;

use App\Models\NbaGame;
use App\Models\NbaScore;

class NbaGameRepository 
{
    public function list(array $filters)
    {
        
    }

    public function show(NbaGame $nbaGame)
    {
        
    }

    public function create(array $data, NbaScore $awayScore, NbaScore $homeScore): NbaGame
    {
        return NbaGame::create([
            'sportsnet_id' => $data['sportsnet_id'] ?? null,
            'away_score_id' => $awayScore->id,
            'home_score_id' => $homeScore->id,
            'started_at' => $data['started_at'],
        ]);
    }
}