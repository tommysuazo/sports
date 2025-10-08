<?php

namespace App\Console\Commands;

use App\Services\NhlExternalService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ImportNhlGames extends Command
{
    /**
     * artisan nhl:import-games
     */
    protected $signature = 'nhl:import-games {--today} {--current}';

    protected $description = 'Importa los juegos de la NHL según el rango solicitado.';

    public function __construct(
        protected NhlExternalService $nhlExternalService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $seasonStart = Carbon::create(2025, 10, 7)->startOfDay();
        $seasonEnd = Carbon::create(2026, 6, 30)->endOfDay();

        $todayOption = (bool) $this->option('today');
        $currentOption = (bool) $this->option('current');

        if ($todayOption && $currentOption) {
            $this->error('No puedes combinar las opciones --today y --current.');
            return Command::FAILURE;
        }

        $today = Carbon::today();

        if ($todayOption) {
            $rangeStart = $today->copy()->subDay();
            $rangeEnd = $today->copy();
        } elseif ($currentOption) {
            $rangeStart = $seasonStart->copy();
            $rangeEnd = $today->copy()->endOfMonth();
        } else {
            $rangeStart = $today->copy()->startOfMonth();
            if ($today->year === 2025 && $today->month === 10) {
                $rangeStart = Carbon::create(2025, 10, 7);
            }
            $rangeEnd = $today->copy()->endOfMonth();
        }

        $startDate = $rangeStart->copy();
        if ($startDate->lt($seasonStart)) {
            $startDate = $seasonStart->copy();
        }

        $endDate = $rangeEnd->copy();
        if ($endDate->gt($seasonEnd)) {
            $endDate = $seasonEnd->copy();
        }

        if ($startDate->gt($endDate)) {
            $this->error('El intervalo seleccionado está fuera de la temporada 2025-2026 de la NHL.');
            return Command::FAILURE;
        }

        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $date = $currentDate->toDateString();
            $this->info("Importando juegos NHL para la fecha {$date}...");
            $this->nhlExternalService->importGamesByDate($date);
            $currentDate->addDay();
        }

        Cache::tags(['nhl-player-stats'])->flush();

        $this->info('Importación completada correctamente.');

        return Command::SUCCESS;
    }
}
