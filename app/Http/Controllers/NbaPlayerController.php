<?php

namespace App\Http\Controllers;

use App\Models\NbaPlayer;
use Illuminate\Http\Request;

class NbaPlayerController extends Controller
{
    public function getScores(NbaPlayer $player)
    {
        return $player->load('scores.games');
    }
}
