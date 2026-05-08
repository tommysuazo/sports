<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WnbaPlayerStat extends Model
{
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
        return $this->belongsTo(WnbaPlayer::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(WnbaTeam::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(WnbaGame::class);
    }
}
