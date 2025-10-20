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

    public function updateOrCreate(array $data, $externalId, NbaTeam $awayTeam, NbaTeam $homeTeam): NbaGame
    {
        return NbaGame::updateOrCreate([
            'external_id' => $externalId,
        ],[
            'away_team_id' => $awayTeam->id,
            'home_team_id' => $homeTeam->id,
            'start_at' => $data['start_at'],
        ]);
    }
}