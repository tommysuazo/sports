<?php

namespace App\Console\Commands;

use App\Models\NbaGame;
use App\Models\NbaPlayer;
use App\Models\NbaTeam;
use App\Services\DigitalSportsTechService;
use App\Services\NbaExternalService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncNbaRosters extends Command
{
    /**
     * artisan nba:import-games
     */
    protected $signature = 'nba:sync-rosters';

    protected $description = 'Importa los rosters de todos los equipos de la NBA.';

    public function __construct(
        protected NbaExternalService $NbaExternalService,
        protected DigitalSportsTechService $digitalSportsTechService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $teams = NbaTeam::all();

        NbaPlayer::where('id', '!=', 0)->update(['team_id' => null]);

        foreach (NbaExternalService::getPlayers() as $playerData) {
            NbaPlayer::updateOrCreate([
                'external_id' => $playerData['external_id'],
            ], [
                'first_name' => transliterator_transliterate('Any-Latin; Latin-ASCII', $playerData['first_name']),
                'last_name' => transliterator_transliterate('Any-Latin; Latin-ASCII', $playerData['last_name']),
                'team_id' => $teams->firstWhere('external_id', $playerData['team_external_id'])?->id,
            ]);
        }

        $this->digitalSportsTechService->syncNbaPlayerMarketIds();

        Cache::tags(['nba-player-stats'])->flush();

        $this->info('Sincronización completada correctamente.');

        return Command::SUCCESS;
    }
}
