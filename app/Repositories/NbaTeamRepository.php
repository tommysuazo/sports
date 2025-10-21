<?php

namespace App\Repositories;

use App\Models\NbaGame;
use App\Models\NbaTeam;

class NbaTeamRepository extends MultiLeagueRepository
{
    protected string $defaultLeague = 'nba';

    protected array $modelMap = [
        'nba' => NbaTeam::class,
        'wnba' => NbaTeam::class,
    ];

    public function syncRecordWithGames(NbaTeam $team): void
    {
        $record = NbaGame::query()
            ->join('nba_team_stats as team_score', function ($join) use ($team) {
                $join->on('team_score.game_id', '=', 'nba_games.id')
                    ->where('team_score.team_id', '=', $team->id);
            })
            ->join('nba_team_stats as opponent_score', function ($join) {
                $join->on('opponent_score.game_id', '=', 'nba_games.id')
                    ->whereColumn('opponent_score.team_id', '!=', 'team_score.team_id');
            })
            ->where('nba_games.is_completed', true)
            ->where(function ($query) use ($team) {
                $query->where('nba_games.home_team_id', $team->id)
                    ->orWhere('nba_games.away_team_id', $team->id);
            })
            ->selectRaw('
                SUM(CASE WHEN team_score.points > opponent_score.points THEN 1 ELSE 0 END) as wins,
                SUM(CASE WHEN team_score.points < opponent_score.points THEN 1 ELSE 0 END) as losses
            ')
            ->first();

        $team->wins = (int) ($record?->wins ?? 0);
        $team->losses = (int) ($record?->losses ?? 0);
        $team->save();
    }
}
