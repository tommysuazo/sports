<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

abstract class BasketballTeam extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'external_id',
        'market_id',
        'short_name',
        'city',
    ];

    abstract public function players(): HasMany;

    abstract public function homeGames(): HasMany;

    abstract public function awayGames(): HasMany;

    abstract public function scores(): HasMany;
}
