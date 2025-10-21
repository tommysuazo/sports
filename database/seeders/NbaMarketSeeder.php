<?php

namespace Database\Seeders;

use App\Services\NbaMarketService;
use Illuminate\Database\Seeder;

class NbaMarketSeeder extends Seeder
{
    public function __construct(
        protected NbaMarketService $nbaMarketService,
    ) {
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->nbaMarketService->syncMarkets();
    }
}
