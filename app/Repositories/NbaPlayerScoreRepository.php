<?php

namespace App\Repositories;

use App\Models\NbaGame;
use App\Models\NbaPlayer;
use App\Models\NbaPlayerScore;
use App\Models\NbaTeam;

class NbaPlayerScoreRepository
{
    public function list(array $filters) {}

    public function show(NbaGame $nbaGame) {}

    public function create(array $data, NbaGame $nbaGame, NbaTeam $nbaTeam, NbaPlayer $nbaPlayer): NbaPlayerScore
    {
        return NbaPlayerScore::create([
            'game_id' => $nbaGame->id,
            'team_id' => $nbaTeam->id,
            'player_id' => $nbaPlayer->id,
            'is_away' => $data['is_away'],
            'is_starter' => $data['is_starter'],
            'mins' => $data['mins'],
            'points' => $data['points'],
            'assists' => $data['assists'],
            'rebounds' => $data['rebounds'],
            'steals' => $data['steals'],
            'blocks' => $data['blocks'],
            'turnovers' => $data['turnovers'],
            'fouls' => $data['fouls'],
            'field_goals_made' => $data['field_goals_made'],
            'field_goals_attempted' => $data['field_goals_attempted'],
            'three_pointers_made' => $data['three_pointers_made'],
            'three_pointers_attempted' => $data['three_pointers_attempted'],
            'free_throws_made' => $data['free_throws_made'],
            'free_throws_attempted' => $data['free_throws_attempted'],
        ]);
    }
}
