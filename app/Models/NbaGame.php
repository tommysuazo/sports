<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NbaGame extends Model
{
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
    ];

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

    public function injuries(): HasMany
    {
        return $this->hasMany(NbaInjury::class, 'game_id');
    }
}
