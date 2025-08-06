<?php

namespace App\Repositories;

use App\Models\NbaGame;
use App\Models\NbaTeam;
use App\Models\WnbaGame;

class NbaGameRepository extends MultiLeagueRepository
{
    protected string $defaultLeague = 'nba';

    protected array $modelMap = [
        'nba' => NbaGame::class,
        'wnba' => WnbaGame::class,
    ];

    public function list(array $filters)
    {
        
    }

    public function findByExternalId(string $externalId): NbaGame|null
    {
        return $this->model::firstWhere('external_id', $externalId);
    }

    public function create(array $data, NbaTeam $awayTeam, NbaTeam $homeTeam): NbaGame
    {
        return $this->model::create([
            'external_id' => $data['external_id'] ?? null,
            'away_team_id' => $awayTeam->id,
            'home_team_id' => $homeTeam->id,
            'started_at' => $data['started_at'],
        ]);
    }
}