<?php

namespace App\Services;

use App\Models\NflGame;
use App\Repositories\NflGameRepository;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class NflGameService
{
    public function __construct(
        protected NflExternalService $nflExternalService
    ){
    }

    public function list()
    {
        return NflGame::all();
    }

    public function importGamesByDateRange(array $data)
    {
        $year = $data['year'] ?? 2025;

        for ($week = $data['from']; $week <= $data['to']; $week++) {
            $this->nflExternalService->importGamesByWeek($week, $year);
        }
    }
}