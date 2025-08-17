<?php

namespace App\Models;

class NcaabPlayerScore extends BasketballPlayerScore
{
    public function player()
    {
        return $this->belongsTo(NcaabPlayer::class);
    }

    public function game()
    {
        return $this->belongsTo(NcaabGame::class);
    }
}
