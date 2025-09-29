<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NflPlayerStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'player_id',
        'team_id',
        'is_away',
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

    protected $casts = [
        'is_away' => 'boolean',
    ];

    public function game()
    {
        return $this->belongsTo(NflGame::class, 'game_id');
    }

    public function player()
    {
        return $this->belongsTo(NflPlayer::class, 'player_id');
    }

    public function team()
    {
        return $this->belongsTo(NflTeam::class, 'team_id');
    }

    public function markets()
    {
        return $this->hasOne(NflPlayerMarket::class, 'game_id', 'game_id');
    }
}
