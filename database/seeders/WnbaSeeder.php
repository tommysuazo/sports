<?php

namespace Database\Seeders;

use App\Enums\DigitalSportsTech\DigitalSportsTechWnbaEnum;
use App\Enums\WNBA\WnbaTeamExternalDataEnum;
use App\Models\NbaPlayer;
use App\Models\NbaTeam;
use App\Models\WnbaPlayer;
use App\Models\WnbaTeam;
use App\Services\DigitalSportsTechService;
use App\Services\NbaExternalService;
use App\Services\WnbaExternalService;
use Illuminate\Database\Seeder;

class WnbaSeeder extends Seeder
{
    public function __construct(
        protected DigitalSportsTechService $digitalSportsTechService
    ) {
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teams = [
            [
                'external_id' => WnbaTeamExternalDataEnum::ATL->value,
                'market_id' => DigitalSportsTechWnbaEnum::getTeamId('ATL'),
                'short_name' => 'ATL', 'city' => 'Atlanta', 'name' => 'Dream'
            ],
            [
                'external_id' => WnbaTeamExternalDataEnum::CHI->value,
                'market_id' => DigitalSportsTechWnbaEnum::getTeamId('CHI'),
                'short_name' => 'CHI', 'city' => 'Chicago', 'name' => 'Sky'
            ],
            [
                'external_id' => WnbaTeamExternalDataEnum::CON->value,
                'market_id' => DigitalSportsTechWnbaEnum::getTeamId('CON'),
                'short_name' => 'CON', 'city' => 'Connecticut', 'name' => 'Sun'
            ],
            [
                'external_id' => WnbaTeamExternalDataEnum::DAL->value,
                'market_id' => DigitalSportsTechWnbaEnum::getTeamId('DAL'),
                'short_name' => 'DAL', 'city' => 'Dallas', 'name' => 'Wings'
            ],
            [
                'external_id' => WnbaTeamExternalDataEnum::GSW->value,
                'market_id' => DigitalSportsTechWnbaEnum::getTeamId('GSW'),
                'short_name' => 'GSW', 'city' => 'Golden State', 'name' => 'Valkyries'
            ],
            [
                'external_id' => WnbaTeamExternalDataEnum::IND->value,
                'market_id' => DigitalSportsTechWnbaEnum::getTeamId('IND'),
                'short_name' => 'IND', 'city' => 'Indiana', 'name' => 'Fever'
            ],
            [
                'external_id' => WnbaTeamExternalDataEnum::LAS->value,
                'market_id' => DigitalSportsTechWnbaEnum::getTeamId('LAS'),
                'short_name' => 'LAS', 'city' => 'Los Angeles', 'name' => 'Sparks'
            ],
            [
                'external_id' => WnbaTeamExternalDataEnum::LVA->value,
                'market_id' => DigitalSportsTechWnbaEnum::getTeamId('LVA'),
                'short_name' => 'LVA', 'city' => 'Las Vegas', 'name' => 'Aces'
            ],
            [
                'external_id' => WnbaTeamExternalDataEnum::MIN->value,
                'market_id' => DigitalSportsTechWnbaEnum::getTeamId('MIN'),
                'short_name' => 'MIN', 'city' => 'Minnesota', 'name' => 'Lynx'
            ],
            [
                'external_id' => WnbaTeamExternalDataEnum::NYL->value,
                'market_id' => DigitalSportsTechWnbaEnum::getTeamId('NYL'),
                'short_name' => 'NYL', 'city' => 'New York', 'name' => 'Liberty'
            ],
            [
                'external_id' => WnbaTeamExternalDataEnum::PHO->value,
                'market_id' => DigitalSportsTechWnbaEnum::getTeamId('PHO'),
                'short_name' => 'PHO', 'city' => 'Phoenix', 'name' => 'Mercury'
            ],
            [
                'external_id' => WnbaTeamExternalDataEnum::SEA->value,
                'market_id' => DigitalSportsTechWnbaEnum::getTeamId('SEA'),
                'short_name' => 'SEA', 'city' => 'Seattle', 'name' => 'Storm'
            ],
            [
                'external_id' => WnbaTeamExternalDataEnum::WAS->value,
                'market_id' => DigitalSportsTechWnbaEnum::getTeamId('WAS'),
                'short_name' => 'WAS', 'city' => 'Washington', 'name' => 'Mystics'
            ],
        ];

        NbaTeam::insert($teams);

        $teams = NbaTeam::all();

        $players = [];

        foreach (NbaExternalService::getWnbaPlayersData() as $playerData) {
            $players[] = [
                'external_id' => $playerData[0],
                'first_name' => transliterator_transliterate('Any-Latin; Latin-ASCII', $playerData[2]),
                'last_name' => transliterator_transliterate('Any-Latin; Latin-ASCII', $playerData[1]),
                'team_id' => $teams->firstWhere('external_id', $playerData[4])?->id,
            ];
        }

        NbaPlayer::insert($players);

        $this->digitalSportsTechService->syncNbaPlayerMarketIds(); // Asegúrate que este método esté definido
    }
}
