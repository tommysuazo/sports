<?php

namespace Database\Seeders;

use App\Services\DigitalSportsTechService;
use Illuminate\Database\Seeder;

class NbaMarketSeeder extends Seeder
{
    public function __construct(
        protected DigitalSportsTechService $digitalSportsTechService,
    ) {
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->digitalSportsTechService->syncNbaMarkets();
    }
}
