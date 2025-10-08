<?php

namespace App\Console\Commands;

use App\Services\NhlMarketService;
use Illuminate\Console\Command;

class SyncNhlMarkets extends Command
{
    protected $signature = 'nhl:sync-markets {market_id? : ID del mercado a sincronizar}';

    protected $description = 'Sincroniza los mercados NHL desde la fuente externa.';

    public function __construct(private readonly NhlMarketService $nhlMarketService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $marketId = $this->argument('market_id');

        if ($marketId) {
            $this->info("Sincronizando mercado {$marketId}...");
        } else {
            $this->info('Sincronizando todos los mercados NHL disponibles...');
        }

        $this->nhlMarketService->syncMarkets($marketId ?: null);

        $this->info('Sincronización completada.');

        return Command::SUCCESS;
    }
}
