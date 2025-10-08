<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NhlGameMarket extends Model
{
    use HasFactory;

    public $fillable = [
        'game_id',
        'favorite_team_id',
        'handicap',
        'total_points',
        'away_team_solo_points',
        'home_team_solo_points',
    ];
}
