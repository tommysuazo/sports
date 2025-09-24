<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NcaabPlayerScore extends BasketballPlayerScore
{
    public function player(): BelongsTo
    {
        return $this->belongsTo(NcaabPlayer::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(NcaabGame::class);
    }
}
