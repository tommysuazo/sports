<?php

namespace App\Repositories;

use App\Models\NbaTeam;

class NbaTeamRepository 
{

    public function updateAverageStats(array $data, NbaTeam $nbaTeam)
    {
        $nbaTeam->update([
            'average_points' => $data['average_points'],
            'average_points_against' => $data['average_points_against'],
            'average_first_half_points' => $data['average_first_half_points'],
            'average_first_half_points_against' => $data['average_first_half_points_against'],
            'average_second_half_points' => $data['average_second_half_points'],
            'average_second_half_points_against' => $data['average_second_half_points_against'],
            'average_first_quarter_points' => $data['average_first_quarter_points'],
            'average_first_quarter_points_against' => $data['average_first_quarter_points_against'],
            'average_second_quarter_points' => $data['average_second_quarter_points'],
            'average_second_quarter_points_against' => $data['average_second_quarter_points_against'],
            'average_third_qeuarter_points' => $data['average_third_qeuarter_points'],
            'average_third_quarter_points_against' => $data['average_third_quarter_points_against'],
            'average_fourth_quarter_points' => $data['average_fourth_quarter_points'],
            'average_fourth_quarter_points_against' => $data['average_fourth_quarter_points_against'],
            'average_assists' => $data['average_assists'],
            'average_assists_against' => $data['average_assists_against'],
            'average_rebounds' => $data['average_rebounds'],
            'average_rebounds_against' => $data['average_rebounds_against'],
            'average_steals' => $data['average_steals'],
            'average_steals_against' => $data['average_steals_against'],
            'average_blocks' => $data['average_blocks'],
            'average_blocks_against' => $data['average_blocks_against'],
            'average_turnovers' => $data['average_turnovers'],
            'average_turnovers_against' => $data['average_turnovers_against'],
            'average_fouls' => $data['average_fouls'],
            'average_fouls_against' => $data['average_fouls_against'],
            'average_field_goals_made' => $data['average_field_goals_made'],
            'average_field_goals_made_against' => $data['average_field_goals_made_against'],
            'average_field_goals_attempted' => $data['average_field_goals_attempted'],
            'average_field_goals_attempted_against' => $data['average_field_goals_attempted_against'],
            'average_three_pointers_made' => $data['average_three_pointers_made'],
            'average_three_pointers_made_against' => $data['average_three_pointers_made_against'],
            'average_three_pointers_attempted' => $data['average_three_pointers_attempted'],
            'average_three_pointers_attempted_against' => $data['average_three_pointers_attempted_against'],
            'average_free_throws_made' => $data['average_free_throws_made'],
            'average_free_throws_made_against' => $data['average_free_throws_made_against'],
            'average_free_throws_attempted' => $data['average_free_throws_attempted'],
            'average_free_throws_attempted_against' => $data['average_free_throws_attempted_against'],
        ]);
    }
}