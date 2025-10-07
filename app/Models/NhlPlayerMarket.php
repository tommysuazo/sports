<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NhlPlayerMarket extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'player_id',
        'goals',
        'shots',
        'assists',
        'points',
        'saves',
    ];
}
