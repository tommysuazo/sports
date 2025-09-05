<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NflGame extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'season',
        'week',
        'played_at',
        'home_team_id',
        'away_team_id',
    ];

    protected $casts = [
        'played_at' => 'date',
    ];

    public function homeTeam()
    {
        return $this->belongsTo(NflTeam::class, 'home_team_id');
    }

    public function awayTeam()
    {
        return $this->belongsTo(NflTeam::class, 'away_team_id');
    }

    public function scores()
    {
        return $this->hasMany(NflTeamStat::class, 'game_id');
    }

    public function playerStats()
    {
        return $this->hasMany(NflPlayerStat::class, 'game_id');
    }
}
