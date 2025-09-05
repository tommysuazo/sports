<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NflPlayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'market_id',
        'team_id',
        'first_name',
        'last_name',
        'position',
    ];

    public function team()
    {
        return $this->belongsTo(NflTeam::class, 'team_id');
    }

    public function stats()
    {
        return $this->hasMany(NflPlayerStat::class, 'player_id');
    }
}
