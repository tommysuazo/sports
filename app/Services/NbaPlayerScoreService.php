<?php

namespace App\Services;

use App\Models\NbaGame;
use App\Repositories\NbaPlayerScoreRepository;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Http;

class NbaPlayerScoreService
{
    public function __construct(
        protected NbaPlayerScoreRepository $nbaPlayerScoreRepository,
        protected SportsnetService $sportsnetService,
    ){
    }

    public function list()
    {
        return NbaGame::all();
    }

    public function create(array $data)
    {
        
    }

}