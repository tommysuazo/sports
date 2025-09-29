<?php

namespace Database\Seeders;

use App\Enums\DigitalSportsTech\DigitalSportsTechNflEnum;
use App\Models\NflPlayer;
use App\Models\NflTeam;
use App\Services\NflExternalService;
use App\Services\NflMarketService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NflSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Equipos
        $teams = NflTeam::all();

        Log::info('Inicio de migracion de equipos de NFL');

        $data = NflExternalService::getTeams();
        
        if (!empty($data['sports'][0]['leagues'][0]['teams'])) {
            foreach ($data['sports'][0]['leagues'][0]['teams'] as $teamData) {
                // Algunos elementos pueden estar vacíos, los saltamos
                if (empty($teamData['team'])) {
                    continue;
                }

                $team = $teamData['team'];

                Log::info("- Equipo a procesar: {$team['location']} {$team['name']}");

                $teams[$team['id']] = NflTeam::updateOrCreate(
                    ['external_id' => $team['id']], // Evita duplicados
                    [
                        'market_id' => DigitalSportsTechNflEnum::getTeamId($team['abbreviation']),
                        'code' => $team['abbreviation'],
                        'name' => $team['name'] ?? null,
                        'city' => $team['location'] ?? null,
                    ]
                );
            }
        }

        // Jugadores
        $players = [];

        Log::info('Inicio de migracion de jugadores de NFL');

        foreach ($teams as $team) {
            Log::info("- Procesando roster del equipo {$team->city} {$team->name}");
            $data = NflExternalService::getTeamPlayers($team);

            foreach ($data['athletes'] as $category) {
                foreach ($category['items'] as $player) {
                    // Ignorar elementos vacíos
                    if (empty($player) || !isset($player['id'])) {
                        continue;
                    }

                    Log::info("** Procesando jugador ID:{$player['id']} {$player['firstName']} {$player['lastName']}");
    
                    NflPlayer::updateOrCreate(
                        ['external_id' => $player['id']],
                        [
                            'team_id' => $team->id,
                            'first_name' => $player['firstName'],
                            'last_name' => $player['lastName'],
                            'position' => $player['position']['abbreviation'],
                        ]
                    );
                }
            }
        }

        resolve(NflMarketService::class)->syncPlayers();

        Cache::tags(['player-stats'])->flush();
    }
}
