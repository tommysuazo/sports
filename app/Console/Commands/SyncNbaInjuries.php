<?php

namespace App\Console\Commands;

use App\Services\NbaInjuryService;
use Illuminate\Console\Command;

class SyncNbaInjuries extends Command
{
    protected $signature = 'nba:sync-injuries';

    protected $description = 'Sincroniza la información de jugadores inactivos para los juegos NBA del día.';

    public function __construct(
        protected NbaInjuryService $nbaInjuryService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $summary = $this->nbaInjuryService->syncTodayInjuries();

        $this->info("Juegos procesados: {$summary['games_processed']}");
        $this->info("Lesiones creadas: {$summary['injuries_created']}");
        $this->info("Lesiones eliminadas: {$summary['injuries_removed']}");

        if (!empty($summary['missing_games'])) {
            $this->warn('Juegos no encontrados en la base de datos: ' . implode(', ', $summary['missing_games']));
        }

        if (!empty($summary['missing_players'])) {
            $this->warn('Jugadores sin registro local:');
            foreach ($summary['missing_players'] as $gameId => $playerIds) {
                $this->warn(" - Juego {$gameId}: " . implode(', ', $playerIds));
            }
        }

        $this->info('Sincronización de lesiones completada correctamente.');

        return Command::SUCCESS;
    }
}
