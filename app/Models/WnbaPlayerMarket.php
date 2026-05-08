<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WnbaPlayerMarket extends Model
{
    protected $fillable = [
        'game_id',
        'player_id',
        'points',
        'assists',
        'rebounds',
        'pt3',
        'pra',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(WnbaGame::class, 'game_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(WnbaPlayer::class, 'player_id');
    }
}
