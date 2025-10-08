<?php

namespace Database\Seeders;

use App\Enums\DigitalSportsTech\DigitalSportsTechNhlEnum;
use App\Models\NhlPlayer;
use App\Models\NhlTeam;
use App\Services\NhlExternalService;
use App\Services\NhlMarketService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NhlSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Equipos

        Log::info('Inicio de migracion de equipos de NHL');

        $data = NhlExternalService::getTeams();

        $allTeamInfo = [];
        
        if (!empty($data['standings'])) {
            foreach ($data['standings'] as $teamData) {
                if (empty($teamData['teamAbbrev']) || empty($teamData['teamAbbrev']['default'])) {
                    continue;
                }

                $allTeamInfo[$teamData['teamAbbrev']['default']] = $teamData;
            }
        }

        ksort($allTeamInfo);

        $teams = [];

        foreach ($allTeamInfo as $teamInfo) {
            Log::info("- Equipo a procesar: " . $teamInfo['teamAbbrev']['default']);

            $teams[] = NhlTeam::updateOrCreate(
                ['code' => $teamInfo['teamAbbrev']['default']], // Evita duplicados
                [
                    'market_id' => DigitalSportsTechNhlEnum::getTeamId($teamInfo['teamAbbrev']['default']),
                    'name' => $teamInfo['teamCommonName']['default'],
                    'city' => $teamInfo['placeName']['default'],
                ]
            );
        }

        // Jugadores

        Log::info('Inicio de migracion de jugadores de NHL');

        foreach ($teams as $team) {
            Log::info("- Procesando roster del equipo {$team->city} {$team->name}");
            $data = NhlExternalService::getTeamPlayers($team);

            foreach ($data as $category) {
                foreach ($category as $player) {
                    // Ignorar elementos vacíos
                    if (empty($player) || !isset($player['id'])) {
                        continue;
                    }

                    Log::info(
                        "** Procesando jugador ID:{$player['id']} {$player['firstName']['default']} {$player['lastName']['default']}"
                    );
    
                    NhlPlayer::updateOrCreate(
                        ['external_id' => $player['id']],
                        [
                            'team_id' => $team->id,
                            'first_name' => $player['firstName']['default'],
                            'last_name' => $player['lastName']['default'],
                            'position' => $player['positionCode'],
                        ]
                    );
                }
            }
        }

        resolve(NhlMarketService::class)->syncPlayers();

        Cache::tags(['nhl-player-stats'])->flush();
    }
}
