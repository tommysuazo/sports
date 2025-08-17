<?php

namespace App\Models;

class WnbaPlayerScore extends BasketballPlayerScore
{
    public function player()
    {
        return $this->belongsTo(WnbaPlayer::class);
    }

    public function game()
    {
        return $this->belongsTo(WnbaGame::class);
    }
}
