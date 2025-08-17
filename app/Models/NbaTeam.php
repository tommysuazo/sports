<?php

namespace App\Models;

use App\Enums\Games\NbaGameStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NbaTeam extends BasketballTeam
{
    public function players(): HasMany
    {
        return $this->hasMany(NbaPlayer::class, 'team_id');
    }

    public function homeGames(): HasMany
    {
        return $this->hasMany(NbaGame::class, 'home_team_id')
            ->where('status', NbaGameStatus::FINAL->value);
    }

    public function awayGames(): HasMany
    {
        return $this->hasMany(NbaGame::class, 'away_team_id')
            ->where('status', NbaGameStatus::FINAL->value);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(NbaTeamScore::class, 'team_id', 'id');
    }
}
