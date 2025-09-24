<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WnbaPlayerScore extends Model
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
