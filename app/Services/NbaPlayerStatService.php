<?php

namespace App\Services;

use App\Models\NbaGame;
use App\Repositories\NbaPlayerStatRepository;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Http;

class NbaPlayerStatService
{
    public function __construct(
        protected NbaPlayerStatRepository $nbaPlayerStatRepository,
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
