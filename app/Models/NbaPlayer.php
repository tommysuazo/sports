<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NbaPlayer extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'external_id',
        'market_id',
        'first_name',
        'last_name',
        'team_id',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(NbaTeam::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(NbaPlayerScore::class, 'player_id');
    }

    public function awayScores(): HasMany
    {
        return $this->scores()
            ->whereHas('game', fn($query) => $query->whereRaw('nba_games.away_team_id = nba_player_scores.team_id'));
    }

    public function homeScores(): HasMany
    {
        return $this->scores()
            ->whereHas('game', fn($query) => $query->whereRaw('nba_games.home_team_id = nba_player_scores.team_id'));
    }
    
    public function againstRivalScores(BasketballTeam $rivalTeam): HasMany
    {
        return $this->scores()->whereHas('game', function ($query) use ($rivalTeam) {
            $query->where('home_team_id', $rivalTeam->id)->orWhere('away_team_id', $rivalTeam->id);
        });
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function markets(): HasMany
    {
        return $this->hasMany(NbaPlayerMarket::class, 'player_id');
    }

    public function currentMarket(): HasOne
    {
        return $this->hasOne(NbaPlayerMarket::class, 'player_id')->latestOfMany();
    }
}
