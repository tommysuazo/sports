<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WnbaPlayer extends BasketballPlayer
{
    public $timestamps = true;

    public function team(): BelongsTo
    {
        return $this->belongsTo(WnbaTeam::class);
    }

    public function stats(): HasMany
    {
        return $this->hasMany(WnbaPlayerStat::class, 'player_id');
    }

    public function scores(): HasMany
    {
        return $this->stats();
    }

    public function markets(): HasMany
    {
        return $this->hasMany(WnbaPlayerMarket::class, 'player_id');
    }

    public function awayStats(): HasMany
    {
        return $this->stats()
            ->whereHas('game', fn($query) => $query->whereRaw('wnba_games.away_team_id = wnba_player_stats.team_id'));
    }

    public function awayScores(): HasMany
    {
        return $this->awayStats();
    }

    public function homeStats(): HasMany
    {
        return $this->stats()
            ->whereHas('game', fn($query) => $query->whereRaw('wnba_games.home_team_id = wnba_player_stats.team_id'));
    }

    public function homeScores(): HasMany
    {
        return $this->homeStats();
    }
    
    public function againstRivalScores(BasketballTeam $rivalTeam): HasMany
    {
        return $this->stats()->whereHas('game', function ($query) use ($rivalTeam) {
            $query->where('home_team_id', $rivalTeam->id)->orWhere('away_team_id', $rivalTeam->id);
        });
    }

    public function currentMarket(): HasOne
    {
        return $this->hasOne(WnbaPlayerMarket::class, 'player_id')->latestOfMany();
    }
}
