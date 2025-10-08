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

    public function game()
    {
        return $this->belongsTo(NhlGame::class, 'game_id');
    }

    public function team()
    {
        return $this->belongsTo(NhlTeam::class, 'team_id');
    }
}
