<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NflGameMarket extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'favorite_team_id',
        'handicap',
        'total_points',
        'first_half_handicap',
        'first_half_points',
        'away_team_solo_points',
        'home_team_solo_points',
    ];
}
