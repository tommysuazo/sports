<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NflTeamStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'team_id',
        'points_total',
        'points_q1',
        'points_q2',
        'points_q3',
        'points_q4',
        'points_ot',
        'total_yards',
        'passing_yards',
        'pass_completions',
        'pass_attempts',
        'rushing_yards',
        'carries',
        'sacks',
        'tackles',
    ];

    public function game()
    {
        return $this->belongsTo(NflGame::class, 'game_id');
    }

    public function team()
    {
        return $this->belongsTo(NflTeam::class, 'team_id');
    }
}
