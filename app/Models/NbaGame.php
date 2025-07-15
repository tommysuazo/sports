<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NbaGame extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'external_id',
        'away_team_id',
        'home_team_id',
        'started_at',
    ];

    public function awayTeam()
    {
        return $this->belongsTo(NbaTeam::class, 'away_team_id');
    }

    public function homeTeam()
    {
        return $this->belongsTo(NbaTeam::class, 'home_team_id');
    }

    public function awayScore()
    {
        return $this->hasOne(NbaTeamScore::class, 'game_id')->where('nba_team_scores.team_id', $this->getAttribute('away_team_id'));
    }

    public function homeScore()
    {
        return $this->hasOne(NbaTeamScore::class, 'game_id')->where('nba_team_scores.team_id', $this->getAttribute('home_team_id'));
    }

    public function scopeWhereTeam($query, $teamId)
    {
        $query->where(function ($queryWhere) use ($teamId) {
            $queryWhere->where('away_team_id', $teamId)
                ->orWhere('home_team_id', $teamId);
        });
    }
}
