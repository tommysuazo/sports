<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NhlTeam extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'market_id',
        'code',
        'name',
        'city',
        'wins',
        'loses',
    ];

    public function players()
    {
        return $this->hasMany(NhlPlayer::class, 'team_id');
    }

    public function homeGames()
    {
        return $this->hasMany(NhlGame::class, 'home_team_id');
    }

    public function awayGames()
    {
        return $this->hasMany(NhlGame::class, 'away_team_id');
    }

    public function stats()
    {
        return $this->hasMany(NhlTeamStat::class, 'team_id');
    }

}
