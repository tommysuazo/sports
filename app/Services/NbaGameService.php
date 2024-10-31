<?php

namespace App\Services;

use App\Models\NbaGame;
use App\Repositories\NbaGameRepository;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Http;

class NbaGameService
{
    public function __construct(
        protected NbaGameRepository $nbaGameRepository,
        protected SportsnetService $sportsnetService,
    ){
    }

    public function list()
    {
        return NbaGame::all();
    }

    public function create(array $data)
    {
        // $period = CarbonPeriod::create(Carbon::parse($data['from_date']), Carbon::parse($data['to_date']));

        // foreach ($period as $date) {
        //     $this->sportsnetService->createManyNbaGamesByDate($date);
        // }

        $this->sportsnetService->createManyNbaGamesByDate(Carbon::now());
    }

}