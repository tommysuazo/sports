<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NcaabGame extends BasketballGame
{
    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(NcaabTeam::class, 'away_team_id');
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(NcaabTeam::class, 'home_team_id');
    }

    public function awayScore(): HasOne
    {
        return $this->hasOne(NcaabTeamScore::class, 'game_id')->where('ncaab_team_scores.team_id', $this->getAttribute('away_team_id'));
    }

    public function homeScore(): HasOne
    {
        return $this->hasOne(NcaabTeamScore::class, 'game_id')->where('ncaab_team_scores.team_id', $this->getAttribute('home_team_id'));
    }
}
