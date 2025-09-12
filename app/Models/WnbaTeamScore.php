<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WnbaTeamScore extends Model
{
    use HasFactory;
    
    public $timestamps = false;
    
    protected $fillable = [
        'game_id',
        'team_id',
        'player_id',
        'locality',
        'is_starter',
        'mins',
        'points',
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

    public function player()
    {
        return $this->belongsTo(NbaPlayer::class);
    }

    public function game()
    {
        return $this->belongsTo(NbaGame::class);
    }
}
