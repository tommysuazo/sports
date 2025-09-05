<?php

namespace App\Repositories;

use App\Models\NbaGame;
use App\Models\NbaTeam;

class NbaGameRepository
{
    public function list(array $filters)
    {
        
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