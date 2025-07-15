<?php

namespace App\Models;

use App\Enums\Games\NbaGameStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NbaTeam extends Model
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

    public function players()
    {
        return $this->hasMany(NbaPlayer::class, 'team_id');
    }

    public function homeGames()
    {
        return $this->hasMany(NbaGame::class, 'home_team_id')
            ->where('status', NbaGameStatus::FINAL->value);
    }

    public function awayGames()
    {
        return $this->hasMany(NbaGame::class, 'away_team_id')
            ->where('status', NbaGameStatus::FINAL->value);
    }
}
