<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NbaPlayerScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'team_id',
        'player_id',
        'is_away',
        'is_starter',
        'mins',
        'points',
        'assists',
        'rebounds',
        'steals',
        'blocks',
        'turnovers',
        'fouls',
        'field_goals_made',
        'field_goals_attempted',
        'three_pointers_made',
        'three_pointers_attempted',
        'free_throws_made',
        'free_throws_attempted',
    ];
    
    public function player(): BelongsTo
    {
        return $this->belongsTo(NbaPlayer::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(NbaGame::class);
    }
}
