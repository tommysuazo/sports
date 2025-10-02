<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NflGame extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'market_id',
        'season',
        'week',
        'played_at',
        'is_completed',
        'home_team_id',
        'away_team_id',
    ];

    protected $casts = [
        'played_at' => 'date',
        'is_completed' => 'boolean',
    ];

    public function homeTeam()
    {
        return $this->belongsTo(NflTeam::class, 'home_team_id');
    }

    public function awayTeam()
    {
        return $this->belongsTo(NflTeam::class, 'away_team_id');
    }

    public function stats()
    {
        return $this->hasMany(NflTeamStat::class, 'game_id', 'id');
    }

    public function playerStats()
    {
        return $this->hasMany(NflPlayerStat::class, 'game_id');
    }

    public function market()
    {
        return $this->hasOne(NflGameMarket::class, 'game_id');
    }

    public function playerMarkets()
    {
        return $this->hasMany(NflPlayerMarket::class, 'game_id');
    }
}
