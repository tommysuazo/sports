<?php

namespace App\Repositories;

use App\Models\NbaPlayer;
use App\Models\NbaTeam;

class NbaPlayerRepository 
{
    public function list()
    {
        return NbaPlayer::all();
    }

    public function create(array $data, NbaTeam $nbaTeam): NbaPlayer
    {
        return NbaPlayer::create([
            'external_id' => $data['external_id'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'team_id' => $nbaTeam->id,
        ]);
    }

    public function updateTeam(NbaPlayer $nbaPlayer, NbaTeam $nbaTeam)
    {
        $nbaPlayer->update(['team_id', $nbaTeam->id]);
    }

    public function clearPlayerMarkets()
    {
        NbaPlayer::query()->update([
            'points_market' => null,
            'assists_market' => null,
            'rebounds_market' => null,
            'pt3_market' => null,
            'pra_market' => null,
            'steals_market' => null,
            'blocks_market' => null,
        ]);
    }
}