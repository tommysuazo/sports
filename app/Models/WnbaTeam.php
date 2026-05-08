<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WnbaTeam extends BasketballTeam
{
    public $timestamps = true;

    protected $fillable = [
        'external_id',
        'market_id',
        'name',
        'short_name',
        'city',
        'wins',
        'losses',
    ];

    public function players(): HasMany
    {
        return $this->hasMany(WnbaPlayer::class, 'team_id');
    }

    public function homeGames(): HasMany
    {
        return $this->hasMany(WnbaGame::class, 'home_team_id');
    }

    public function awayGames(): HasMany
    {
        return $this->hasMany(WnbaGame::class, 'away_team_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(WnbaTeamStat::class, 'team_id', 'id');
    }

    public function stats(): HasMany
    {
        return $this->scores();
    }

    public function markets(): HasMany
    {
        return $this->hasMany(WnbaTeamMarket::class, 'team_id');
    }
}
