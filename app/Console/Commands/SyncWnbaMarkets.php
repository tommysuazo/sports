<?php

namespace App\Console\Commands;

use App\Services\WnbaMarketService;
use Illuminate\Console\Command;

class SyncWnbaMarkets extends Command
{
    protected $signature = 'wnba:sync-markets {market_id? : ID del mercado a sincronizar}';

    protected $description = 'Sincroniza los mercados WNBA desde la fuente externa.';

    public function __construct(private readonly WnbaMarketService $wnbaMarketService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $marketId = $this->argument('market_id');

        if ($marketId) {
            $this->info("Sincronizando mercado WNBA {$marketId}...");
        } else {
            $this->info('Sincronizando todos los mercados WNBA disponibles...');
        }

        $this->wnbaMarketService->syncMarkets($marketId ?: null);

        $this->info('Sincronizacion WNBA completada.');

        return Command::SUCCESS;
    }
}
