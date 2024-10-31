<?php

namespace App\Repositories;

use App\Models\NbaGame;
use App\Models\NbaPlayer;
use App\Models\NbaPlayerScore;
use App\Models\NbaScore;

class NbaPlayerScoreRepository
{
    public function list(array $filters) {}

    public function show(NbaGame $nbaGame) {}

    public function create(array $data, NbaPlayer $nbaPlayer, NbaScore $nbaScore): NbaPlayerScore
    {
        return NbaPlayerScore::create([
            'player_id' => $nbaPlayer->id,
            'score_id' => $nbaScore->id,
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
