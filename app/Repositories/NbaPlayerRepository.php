<?php

namespace App\Repositories;

use App\Models\NbaPlayer;
use App\Models\NbaTeam;
use App\Models\NbaPlayerMarket;

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
        $nbaPlayer->update(['team_id' => $nbaTeam->id]);
    }
}
