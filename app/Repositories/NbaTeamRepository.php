<?php

namespace App\Repositories;

use App\Models\NbaTeam;

class NbaTeamRepository extends MultiLeagueRepository
{
    protected string $defaultLeague = 'nba';

    protected array $modelMap = [
        'nba' => NbaTeam::class,
        'wnba' => NbaTeam::class,
    ];

    public function getTeamsDataForMatchups(array $teamIds, bool $withHomeScores = true)
    {
        $scoreType = $withHomeScores ? 'homeScores' : 'awayScores';

        return NbaTeam::join('nba_team_scores', 'nba_teams.id', 'nba_team_scores.team_id')
            ->with([
                'players' => function ($queryPlayer) use ($scoreType) {
                    $queryPlayer->with([
                        'scores' => fn($queryScore) => $queryScore->orderByDesc('nba_player_scores.id')->limit(5),
                        $scoreType => fn($queryScore) => $queryScore->orderByDesc('nba_player_scores.id')->limit(5),
                    ]);
                },
            ])
        ->selectRaw("
            nba_teams.*,
            ROUND(AVG(nba_team_scores.points), 1) as points_avg,
            ROUND(AVG(nba_team_scores.first_half_points), 1) as first_half_points_avg,
            ROUND(AVG(nba_team_scores.second_half_points), 1) as second_half_points_avg,
            ROUND(AVG(nba_team_scores.first_quarter_points), 1) as first_quarter_points_avg,
            ROUND(AVG(nba_team_scores.second_quarter_points), 1) as second_quarter_points_avg,
            ROUND(AVG(nba_team_scores.third_quarter_points), 1) as third_quarter_points_avg,
            ROUND(AVG(nba_team_scores.fourth_quarter_points), 1) as fourth_quarter_points_avg,
            ROUND(AVG(nba_team_scores.assists), 1) as assists_avg,
            ROUND(AVG(nba_team_scores.rebounds), 1) as rebounds_avg
        ")
        ->whereIn('external_id', $teamIds)
        ->groupBy('nba_teams.id')
        ->get();
    }

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