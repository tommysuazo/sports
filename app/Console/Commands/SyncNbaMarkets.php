<?php

namespace App\Console\Commands;

use App\Services\NbaMarketService;
use Illuminate\Console\Command;

class SyncNbaMarkets extends Command
{
    protected $signature = 'nba:sync-markets {market_id? : ID del mercado a sincronizar}';

    protected $description = 'Sincroniza los mercados NBA desde la fuente externa.';

    public function __construct(private readonly NbaMarketService $nbaMarketService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $marketId = $this->argument('market_id');

        if ($marketId) {
            $this->info("Sincronizando mercado NBA {$marketId}...");
        } else {
            $this->info('Sincronizando todos los mercados NBA disponibles...');
        }

        $this->nbaMarketService->syncMarkets($marketId ?: null);

        $this->info('Sincronización NBA completada.');

        return Command::SUCCESS;
    }
}
