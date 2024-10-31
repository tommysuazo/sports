<?php

namespace App\Repositories;

use App\Models\NbaGame;
use App\Models\NbaScore;
use App\Models\NbaTeam;

class NbaScoreRepository
{
    public function list(array $filters) {}

    public function show(NbaGame $nbaGame) {}

    public function create(array $data, NbaTeam $nbaTeam): NbaScore
    {
        return NbaScore::create([
            'team_id' => $nbaTeam->id,
            'points' => $data['points'],
            'first_half_points' => $data['first_half_points'],
            'second_half_points' => $data['second_half_points'],
            'first_quarter_points' => $data['first_quarter_points'],
            'second_quarter_points' => $data['second_quarter_points'],
            'third_quarter_points' => $data['third_quarter_points'],
            'fourth_quarter_points' => $data['fourth_quarter_points'],
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
