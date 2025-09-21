<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NflPlayerMarket extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'player_id',
        'passing_yards',
        'pass_completions',
        'pass_attempts',
        'receiving_yards',
        'receptions',
        'receiving_targets',
        'rushing_yards',
        'carries',
        'sacks',
        'tackles',
    ];
    
}
