<?php

namespace App\Repositories;

use App\Models\BasketballGame;
use App\Models\BasketballTeam;
use App\Models\NbaGame;
use App\Models\NbaTeam;
use App\Models\NcaabGame;
use App\Models\WnbaGame;

class BasketballGameRepository extends MultiLeagueRepository
{
    protected array $modelMap = [
        'nba' => NbaGame::class,
        'wnba' => WnbaGame::class,
        'ncaab' => NcaabGame::class,
    ];

    public function list(array $filters)
    {
        
    }

    public function findByExternalId(string $externalId): BasketballGame|null
    {
        return $this->model::firstWhere('external_id', $externalId);
    }

    public function create(array $data, BasketballTeam $awayTeam, BasketballTeam $homeTeam): BasketballGame
    {
        return $this->model::create([
            'external_id' => $data['external_id'] ?? null,
            'away_team_id' => $awayTeam->id,
            'home_team_id' => $homeTeam->id,
            'started_at' => $data['started_at'],
        ]);
    }
}