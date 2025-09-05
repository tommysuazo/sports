<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NflTeam extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'market_id',
        'code',
        'name',
        'city',
    ];

    public function players()
    {
        return $this->hasMany(NflPlayer::class, 'team_id');
    }

    public function homeGames()
    {
        return $this->hasMany(NflGame::class, 'home_team_id');
    }

    public function awayGames()
    {
        return $this->hasMany(NflGame::class, 'away_team_id');
    }

    public function scores()
    {
        return $this->hasMany(NflTeamStat::class, 'team_id');
    }
}
