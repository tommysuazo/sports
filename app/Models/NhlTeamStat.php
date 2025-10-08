<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NhlTeamStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'team_id',
        'goals',
        'shots',
    ];
}
