<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NbaTeamScore extends Model
{
    use HasFactory;
    
    public $timestamps = false;

    protected $fillable = [
        'game_id',
        'team_id',
        'points',
        'first_half_points',
        'second_half_points',
        'first_quarter_points',
        'second_quarter_points',
        'third_quarter_points',
        'fourth_quarter_points',
        'overtimes',
        'overtime_points',
        'assists',
        'rebounds',
        'steals',
        'blocks',
        'turnovers',
        'fouls',
        'field_goals_made',
        'field_goals_attempted',
        'three_pointers_made',
        'three_pointers_attempted',
        'free_throws_made',
        'free_throws_attempted',
    ];

    public function team()
    {
        return $this->belongsTo(NbaTeam::class, 'team_id', 'id');
    }

    public function game()
    {
        return $this->belongsTo(NbaGame::class, 'game_id', 'id');
    }
}
