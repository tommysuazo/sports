<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Services\NflExternalService;

class ImportNflGames extends Command
{
    /**
     * artisan nfl:import-games 1 [week_to] [year]
     */
    protected $signature = 'nfl:import-games {week_from} {week_to?} {year=2025}';

    protected $description = 'Import NFL games from week_from to week_to for a given year';

    public function __construct(
        protected NflExternalService $nflExternalService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $weekFrom = (int) $this->argument('week_from');
        $weekTo   = $this->argument('week_to') !== null
            ? (int) $this->argument('week_to')
            : $weekFrom; // si no existe, usa week_from

        $year = (int) $this->argument('year'); // por defecto 2025

        // 🔹 Validaciones
        if ($weekFrom < 1 || $weekFrom > 18) {
            $this->error("week_from debe estar entre 1 y 18.");
            return Command::FAILURE;
        }

        if ($weekTo < 1 || $weekTo > 18) {
            $this->error("week_to debe estar entre 1 y 18.");
            return Command::FAILURE;
        }

        if ($weekTo < $weekFrom) {
            $this->error("week_to no puede ser menor que week_from.");
            return Command::FAILURE;
        }

        if ($weekFrom > $weekTo) {
            $this->error("week_from no puede ser mayor que week_to.");
            return Command::FAILURE;
        }

        // 🔹 Ejecución
        for ($week = $weekFrom; $week <= $weekTo; $week++) {
            $this->info("Importing games for year {$year}, week {$week}...");
            $this->nflExternalService->importGamesByWeek($week, $year);
        }

        Cache::tags(['player-stats'])->flush();

        $this->info('Import completed successfully.');
        return Command::SUCCESS;
    }
}
