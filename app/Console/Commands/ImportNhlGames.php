<?php

namespace App\Console\Commands;

use App\Models\NhlGame;
use App\Services\NhlExternalService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ImportNhlGames extends Command
{
    /**
     * artisan nhl:import-games
     */
    protected $signature = 'nhl:import-games {--all}';

    protected $description = 'Importa los juegos de la NHL según el rango solicitado.';

    public function __construct(
        protected NhlExternalService $nhlExternalService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if ((bool) $this->option('all')) {
            $startDate = Carbon::parse(config('nhl.start_date'));
            $endDate   = Carbon::parse(config('nhl.end_date'));

            while ($startDate->lte($endDate)) {
                $date = $startDate->toDateString();
                $this->info("Importando juegos NHL para la fecha {$date}...");
                $this->nhlExternalService->importGamesByDate($date);
                $startDate->addDay();
            }
        } else {
            // 1) Tomar la última fecha de un juego COMPLETADO; si no existe, usar start_date de la config
            $lastCompletedGameDate = NhlGame::query()
                ->where('is_completed', 1)
                ->orderByDesc('start_at')
                ->value('start_at'); // devuelve string/date o null

            $cursorDate = $lastCompletedGameDate
                ? Carbon::parse($lastCompletedGameDate)->startOfDay()
                : Carbon::parse(config('nhl.start_date'))->startOfDay();

            // Límite superior: no pasar de hoy ni del end_date de la config
            $seasonEnd = Carbon::parse(config('nhl.end_date'))->endOfDay();
            $todayEnd  = Carbon::today()->endOfDay();
            $untilDate = $seasonEnd->lt($todayEnd) ? $seasonEnd : $todayEnd;

            // Si por alguna razón el cursor quedó después del límite, no hacemos nada
            if ($cursorDate->gt($untilDate)) {
                $this->warn('No hay fechas a procesar dentro del rango permitido.');
            } else {
                // 2) Procesar día a día: solo avanzar si el último juego importado está completado
                while ($cursorDate->lte($untilDate)) {
                    $dateStr = $cursorDate->toDateString();
                    $this->info("Importando juegos NHL para la fecha {$dateStr}...");

                    // Debe devolver el último juego importado (o null si no hubo importaciones/errores)
                    $lastImported = $this->nhlExternalService->importGamesByDate($dateStr);

                    // Si no hay resultado o no está completado, se detiene la ejecución
                    if ($lastImported !== null && (int) $lastImported->is_completed !== 1) {
                        $this->info('Se detiene la importación porque el último juego importado no está completado aún.');
                        break;
                    }

                    // Último importado completado: avanzar al día siguiente
                    $cursorDate->addDay();
                }
            }
        }
    

        Cache::tags(['nhl-player-stats'])->flush();

        $this->info('Importación completada correctamente.');

        return Command::SUCCESS;
    }
}
