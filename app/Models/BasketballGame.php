<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

abstract class BasketballGame extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'external_id',
        'away_team_id',
        'home_team_id',
        'started_at',
    ];

    abstract public function awayTeam(): BelongsTo;

    abstract public function homeTeam(): BelongsTo;

    abstract public function awayScore(): HasOne;

    abstract public function homeScore(): HasOne;
}
