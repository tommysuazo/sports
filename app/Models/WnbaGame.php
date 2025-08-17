<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WnbaGame extends BasketballGame
{
    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(WnbaTeam::class, 'away_team_id');
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(WnbaTeam::class, 'home_team_id');
    }

    public function awayScore(): HasOne
    {
        return $this->hasOne(WnbaTeamScore::class, 'game_id')->where('wnba_team_scores.team_id', $this->getAttribute('away_team_id'));
    }

    public function homeScore(): HasOne
    {
        return $this->hasOne(WnbaTeamScore::class, 'game_id')->where('wnba_team_scores.team_id', $this->getAttribute('home_team_id'));
    }
}
