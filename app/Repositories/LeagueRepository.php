<?php

namespace App\Repositories;

use App\Models\League;

class LeagueRepository 
{
    public function list()
    {
        return League::all();
    }
}