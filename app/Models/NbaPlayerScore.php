<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NbaPlayerScore extends BasketballPlayerScore
{
    public function player()
    {
        return $this->belongsTo(NbaPlayer::class);
    }

    public function game()
    {
        return $this->belongsTo(NbaGame::class);
    }
}
