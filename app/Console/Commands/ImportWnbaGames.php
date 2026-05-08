<?php

namespace App\Console\Commands;

use App\Models\WnbaGame;
use App\Services\WnbaExternalService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ImportWnbaGames extends Command
{
    /**
     * artisan wnba:import-games
     */
    protected $signature = 'wnba:import-games {--all}';

    protected $description = 'Importa los juegos de la WNBA segun el rango solicitado.';

    public function __construct(
        protected WnbaExternalService $wnbaExternalService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $excludedDates = collect(config('wnba.exclude_dates', []))
            ->filter()
            ->map(fn (string $date) => Carbon::parse($date)->toDateString())
            ->all();

        if ((bool) $this->option('all')) {
            $startDate = Carbon::parse(config('wnba.start_date'));
            $endDate   = Carbon::parse(config('wnba.end_date'));

            while ($startDate->lte($endDate)) {
                $date = $startDate->toDateString();
                if (in_array($date, $excludedDates, true)) {
                    $this->info("Se omite {$date} por estar configurada como fecha sin juegos WNBA.");
                    $startDate->addDay();
                    continue;
                }

                $this->info("Importando juegos WNBA para la fecha {$date}...");
                $this->wnbaExternalService->importGamesByDate($startDate->copy());
                $startDate->addDay();
            }
        } else {
            $lastCompletedGameDate = WnbaGame::query()
                ->where('is_completed', 1)
                ->orderByDesc('start_at')
                ->value('start_at');

            $cursorDate = $lastCompletedGameDate
                ? Carbon::parse($lastCompletedGameDate)->setTimezone(config('app.user_timezone'))->startOfDay()
                : Carbon::parse(config('wnba.start_date'))->startOfDay();

            $seasonEnd = Carbon::parse(config('wnba.end_date'))->endOfDay();
            $todayEnd  = Carbon::today()->endOfDay();
            $untilDate = $seasonEnd->lt($todayEnd) ? $seasonEnd : $todayEnd;

            if ($cursorDate->gt($untilDate)) {
                $this->warn('No hay fechas a procesar dentro del rango permitido.');
            } else {
                while ($cursorDate->lte($untilDate)) {
                    $dateStr = $cursorDate->toDateString();
                    if (in_array($dateStr, $excludedDates, true)) {
                        $this->info("Se omite {$dateStr} por estar configurada como fecha sin juegos WNBA.");
                        $cursorDate->addDay();
                        continue;
                    }

                    $this->info("Importando juegos WNBA para la fecha {$dateStr}...");

                    $lastImported = $this->wnbaExternalService->importGamesByDate($cursorDate->copy());

                    if ($lastImported !== null && (int) $lastImported->is_completed !== 1) {
                        $this->info('Se detiene la importacion porque el ultimo juego importado no esta completado aun.');
                        break;
                    }

                    $cursorDate->addDay();
                }
            }
        }

        Cache::tags(['wnba-player-stats'])->flush();

        $this->info('Importacion completada correctamente.');

        return Command::SUCCESS;
    }
}
