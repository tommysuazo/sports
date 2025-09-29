<?php

namespace App\Console\Commands;

use App\Services\NflMarketService;
use Illuminate\Console\Command;

class SyncNflMarkets extends Command
{
    protected $signature = 'nfl:sync-markets {market_id? : ID del mercado a sincronizar}';

    protected $description = 'Sincroniza los mercados NFL desde la fuente externa.';

    public function __construct(private readonly NflMarketService $nflMarketService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $marketId = $this->argument('market_id');

        if ($marketId) {
            $this->info("Sincronizando mercado {$marketId}...");
        } else {
            $this->info('Sincronizando todos los mercados NFL disponibles...');
        }

        $this->nflMarketService->syncMarkets($marketId ?: null);

        $this->info('Sincronización completada.');

        return Command::SUCCESS;
    }
}
