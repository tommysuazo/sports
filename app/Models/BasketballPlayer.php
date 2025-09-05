<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

abstract class BasketballPlayer extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'external_id',
        'market_id',
        'first_name',
        'last_name',
        'team_id',
    ];

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    abstract public function team(): BelongsTo;

    abstract public function scores(): HasMany;

    abstract public function awayScores(): HasMany;

    abstract public function homeScores(): HasMany;
    
    abstract public function againstRivalScores(BasketballTeam $rivalTeam): HasMany;
}