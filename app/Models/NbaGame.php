<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NbaGame extends BasketballGame
{
    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(NbaTeam::class, 'away_team_id');
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(NbaTeam::class, 'home_team_id');
    }

    public function awayScore(): HasOne
    {
        return $this->hasOne(NbaTeamScore::class, 'game_id')->where('nba_team_scores.team_id', $this->getAttribute('away_team_id'));
    }

    public function homeScore(): HasOne
    {
        return $this->hasOne(NbaTeamScore::class, 'game_id')->where('nba_team_scores.team_id', $this->getAttribute('home_team_id'));
    }
}
