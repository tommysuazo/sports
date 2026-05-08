<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WnbaGame extends BasketballGame
{
    public $timestamps = true;

    protected $fillable = [
        'external_id',
        'market_id',
        'away_team_id',
        'home_team_id',
        'winner_team_id',
        'start_at',
        'is_completed',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'is_completed' => 'boolean',
    ];

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(WnbaTeam::class, 'away_team_id');
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(WnbaTeam::class, 'home_team_id');
    }

    public function awayStat(): HasOne
    {
        return $this->hasOne(WnbaTeamStat::class, 'game_id')->where('wnba_team_stats.team_id', $this->getAttribute('away_team_id'));
    }

    public function awayScore(): HasOne
    {
        return $this->awayStat();
    }

    public function homeStat(): HasOne
    {
        return $this->hasOne(WnbaTeamStat::class, 'game_id')->where('wnba_team_stats.team_id', $this->getAttribute('home_team_id'));
    }

    public function homeScore(): HasOne
    {
        return $this->homeStat();
    }

    public function stats(): HasMany
    {
        return $this->hasMany(WnbaTeamStat::class, 'game_id');
    }

    public function playerStats(): HasMany
    {
        return $this->hasMany(WnbaPlayerStat::class, 'game_id');
    }

    public function playerScores(): HasMany
    {
        return $this->playerStats();
    }

    public function market(): HasOne
    {
        return $this->hasOne(WnbaGameMarket::class, 'game_id');
    }

    public function teamMarkets(): HasMany
    {
        return $this->hasMany(WnbaTeamMarket::class, 'game_id');
    }

    public function playerMarkets(): HasMany
    {
        return $this->hasMany(WnbaPlayerMarket::class, 'game_id');
    }
}
