<?php

namespace Database\Seeders;

use App\Enums\DigitalSportsTech\DigitalSportsTechNhlEnum;
use App\Models\NhlTeam;
use App\Services\NhlExternalService;
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
        $teams = NhlTeam::all();

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

        foreach ($allTeamInfo as $teamInfo) {
            Log::info("- Equipo a procesar: " . $teamInfo['teamAbbrev']['default']);

            $teams[$teamInfo['id']] = NhlTeam::updateOrCreate(
                ['code' => $teamInfo['teamAbbrev']['default']], // Evita duplicados
                [
                    'market_id' => DigitalSportsTechNhlEnum::getTeamId($teamInfo['teamAbbrev']['default']),
                    'name' => $teamInfo['teamCommonName']['default'],
                    'city' => $teamInfo['placeName']['default'],
                ]
            );
        }

        // Jugadores
        // $players = [];

        // Log::info('Inicio de migracion de jugadores de NFL');

        // foreach ($teams as $team) {
        //     Log::info("- Procesando roster del equipo {$team->city} {$team->name}");
        //     $data = NflExternalService::getTeamPlayers($team);

        //     foreach ($data['athletes'] as $category) {
        //         foreach ($category['items'] as $player) {
        //             // Ignorar elementos vacíos
        //             if (empty($player) || !isset($player['id'])) {
        //                 continue;
        //             }

        //             Log::info("** Procesando jugador ID:{$player['id']} {$player['firstName']} {$player['lastName']}");
    
        //             NflPlayer::updateOrCreate(
        //                 ['external_id' => $player['id']],
        //                 [
        //                     'team_id' => $team->id,
        //                     'first_name' => $player['firstName'],
        //                     'last_name' => $player['lastName'],
        //                     'position' => $player['position']['abbreviation'],
        //                 ]
        //             );
        //         }
        //     }
        // }

        // resolve(NflMarketService::class)->syncPlayers();

        // Cache::tags(['player-stats'])->flush();
    }
}
