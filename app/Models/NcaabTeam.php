<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NcaabTeam extends BasketballTeam
{
    public function players(): HasMany
    {
        return $this->hasMany(NcaabPlayer::class, 'team_id');
    }

    public function homeGames(): HasMany
    {
        return $this->hasMany(NcaabGame::class, 'home_team_id');
    }

    public function awayGames(): HasMany
    {
        return $this->hasMany(NcaabGame::class, 'away_team_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(NcaabTeamScore::class, 'team_id', 'id');
    }
}
