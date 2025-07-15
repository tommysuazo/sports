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
        protected NbaExternalService $nbaExternalService,
        protected NbaGameRepository $nbaGameRepository,
    ){
    }

    public function list()
    {
        return NbaGame::all();
    }

    public function importGamesByDateRange(array $data)
    {
        $period = CarbonPeriod::create(Carbon::parse($data['from']), Carbon::parse($data['to']));

        foreach ($period as $date) {
            $this->nbaExternalService->importGamesByDate($date);
        }

    }

}