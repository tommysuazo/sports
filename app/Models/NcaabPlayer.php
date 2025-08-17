<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NcaabPlayer extends BasketballPlayer
{
    public function team(): BelongsTo
    {
        return $this->belongsTo(NcaabTeam::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(NcaabPlayerScore::class, 'player_id');
    }

    public function awayScores(): HasMany
    {
        return $this->scores()
            ->whereHas('game', fn($query) => $query->whereRaw('ncaab_games.away_team_id = ncaab_player_scores.team_id'));
    }

    public function homeScores(): HasMany
    {
        return $this->scores()
            ->whereHas('game', fn($query) => $query->whereRaw('ncaab_games.home_team_id = nba_player_scores.team_id'));
    }
    
    public function againstRivalScores(BasketballTeam $rivalTeam): HasMany
    {
        return $this->scores()->whereHas('game', function ($query) use ($rivalTeam) {
            $query->where('home_team_id', $rivalTeam->id)->orWhere('away_team_id', $rivalTeam->id);
        });
    }
}
