<?php

namespace App\Console\Commands;

use App\Models\NbaGame;
use App\Services\NbaExternalService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ImportNbaGames extends Command
{
    /**
     * artisan nba:import-games
     */
    protected $signature = 'nba:import-games {--all}';

    protected $description = 'Importa los juegos de la NBA según el rango solicitado.';

    public function __construct(
        protected NbaExternalService $NbaExternalService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $excludedDates = collect(config('nba.exclude_dates', []))
            ->filter()
            ->map(fn (string $date) => Carbon::parse($date)->toDateString())
            ->all();

        if ((bool) $this->option('all')) {
            $startDate = Carbon::parse(config('nba.start_date'));
            $endDate   = Carbon::parse(config('nba.end_date'));

            while ($startDate->lte($endDate)) {
                $date = $startDate->toDateString();
                if (in_array($date, $excludedDates, true)) {
                    $this->info("Se omite {$date} por estar configurada como fecha sin juegos NBA.");
                    $startDate->addDay();
                    continue;
                }

                $this->info("Importando juegos NBA para la fecha {$date}...");
                $this->NbaExternalService->importGamesByDate($startDate->copy());
                $startDate->addDay();
            }
        } else {
            $lastCompletedGameDate = NbaGame::query()
                ->where('is_completed', 1)
                ->orderByDesc('start_at')
                ->value('start_at');

            $cursorDate = $lastCompletedGameDate
                ? Carbon::parse($lastCompletedGameDate)->setTimezone(config('app.user_timezone'))->startOfDay()
                : Carbon::parse(config('nba.start_date'))->startOfDay();

            $seasonEnd = Carbon::parse(config('nba.end_date'))->endOfDay();
            $todayEnd  = Carbon::today()->endOfDay();
            $untilDate = $seasonEnd->lt($todayEnd) ? $seasonEnd : $todayEnd;

            if ($cursorDate->gt($untilDate)) {
                $this->warn('No hay fechas a procesar dentro del rango permitido.');
            } else {
                while ($cursorDate->lte($untilDate)) {
                    $dateStr = $cursorDate->toDateString();
                    if (in_array($dateStr, $excludedDates, true)) {
                        $this->info("Se omite {$dateStr} por estar configurada como fecha sin juegos NBA.");
                        $cursorDate->addDay();
                        continue;
                    }

                    $this->info("Importando juegos NBA para la fecha {$dateStr}...");

                    $lastImported = $this->NbaExternalService->importGamesByDate($cursorDate->copy());

                    if ($lastImported !== null && (int) $lastImported->is_completed !== 1) {
                        $this->info('Se detiene la importación porque el último juego importado no está completado aún.');
                        break;
                    }

                    $cursorDate->addDay();
                }
            }
        }

        Cache::tags(['nba-player-stats'])->flush();

        $this->info('Importación completada correctamente.');

        return Command::SUCCESS;
    }
}
