<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NbaGameMarket extends Model
{
    protected $fillable = [
        'favorite_team_id',
        'handicap',
        'points',
        'first_half_handicap',
        'first_half_points',
        'first_quarter_points',
        'second_quarter_points',
        'third_quarter_points',
        'fourth_quarter_points',
    ];
}
