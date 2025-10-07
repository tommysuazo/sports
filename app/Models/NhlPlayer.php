<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NhlPlayer extends Model
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

    protected $appends = ['full_name'];
    
    /**
     * Atributo calculado: full_name
     * Disponible como $player->full_name y se incluye en JSON.
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(function () {
            // Une nombre y apellido, ignorando nulls/vacíos y limpiando espacios
            $parts = array_filter([$this->first_name, $this->last_name], fn ($v) => filled($v));
            return trim(implode(' ', $parts));
        });
    }

    public function team()
    {
        return $this->belongsTo(NhlTeam::class, 'team_id');
    }

    public function stats()
    {
        return $this->hasMany(NhlPlayerStat::class, 'player_id');
    }

    public function markets()
    {
        return $this->hasMany(NhlPlayerMarket::class, 'player_id');
    }
}
