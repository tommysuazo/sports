<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NhlPlayerStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'player_id',
        'is_starter',
        'time',
        'goals',
        'shots',
        'assists',
        'points',
        'saves',
    ];
}
