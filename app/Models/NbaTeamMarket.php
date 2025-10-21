<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NbaTeamMarket extends Model
{
    protected $fillable = [
        'game_id',
        'team_id',
        'points',
        'first_half_points',
        'first_quarter_points',
        'second_quarter_points',
        'third_quarter_points',
        'fourth_quarter_points',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(NbaGame::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(NbaTeam::class);
    }
}
