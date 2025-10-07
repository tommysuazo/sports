<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NhlGame extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'market_id',
        'season',
        'start_at',
        'is_completed',
        'away_team_id',
        'home_team_id',
        'winner_team_id',
    ];

    protected $casts = [
        'start_at' => 'date',
        'is_completed' => 'boolean',
    ];

    public function homeTeam()
    {
        return $this->belongsTo(NhlTeam::class, 'home_team_id');
    }

    public function awayTeam()
    {
        return $this->belongsTo(NhlTeam::class, 'away_team_id');
    }

    public function stats()
    {
        return $this->hasMany(NhlTeamStat::class, 'game_id', 'id');
    }

    public function playerStats()
    {
        return $this->hasMany(NhlPlayerStat::class, 'game_id');
    }

    public function market()
    {
        return $this->hasOne(NhlGameMarket::class, 'game_id');
    }

    public function playerMarkets()
    {
        return $this->hasMany(NhlPlayerMarket::class, 'game_id');
    }

}
