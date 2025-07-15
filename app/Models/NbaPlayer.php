<?php

namespace App\Models;

use App\Enums\Locality\LocalityEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NbaPlayer extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'external_id',
        'market_id',
        'first_name',
        'last_name',
        'team_id',
        'against_team_id',
        'points_market',
        'assists_market',
        'rebounds_market',
        'pt3_market',
        'pra_market',
        'steals_market',
        'blocks_market',
    ];

    public function team()
    {
        return $this->belongsTo(NbaTeam::class);
    }

    public function scores()
    {
        return $this->hasMany(NbaPlayerScore::class, 'player_id');
    }

    public function awayScores()
    {
        return $this->scores()
            ->whereHas('game', fn($query) => $query->whereRaw('nba_games.away_team_id = nba_player_scores.team_id'));
    }

    public function homeScores()
    {
        return $this->scores()
            ->whereHas('game', fn($query) => $query->whereRaw('nba_games.home_team_id = nba_player_scores.team_id'));
    }
    
    public function againstRivalScores(NbaTeam $rivalTeam)
    {
        return $this->scores()->whereHas('game', function ($query) use ($rivalTeam) {
            $query->where('home_team_id', $rivalTeam->id)->orWhere('away_team_id', $rivalTeam->id);
        });
    }
    

    public function scopeHasMarkets($query) 
    {
        $query->where(
            fn($queryMarket) => $queryMarket
                ->whereNotNull('points_market')
                ->orWhereNotNull('assists_market')
                ->orWhereNotNull('rebounds_market')
                ->orWhereNotNull('pra_market')
                ->orWhereNotNull('pt3_market')
        );
        
    }
    
    public function scopeSelectFullname($query) {
        return $query->addSelect(DB::raw("CONCAT(nba_players.first_name, ' ', nba_players.last_name) as full_name"));
    }
}