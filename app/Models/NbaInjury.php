<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NbaInjury extends Model
{
    use HasFactory;

    protected $primaryKey = null;

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'game_id',
        'team_id',
        'player_id',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(NbaGame::class, 'game_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(NbaPlayer::class, 'player_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(NbaTeam::class, 'team_id');
    }
}
