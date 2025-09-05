<?php

namespace Database\Seeders;

use App\Enums\DigitalSportsTech\DigitalSportsTechNbaEnum;
use App\Enums\NBA\NbaTeamExternalDataEnum;
use App\Models\NbaPlayer;
use App\Models\NbaTeam;
use App\Services\DigitalSportsTechService;
use App\Services\NbaExternalService;
use App\Services\NbaStatsService;
use Illuminate\Database\Seeder;

class NbaSeeder extends Seeder
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
                'external_id' => NbaTeamExternalDataEnum::BOS->value,
                'market_id' => DigitalSportsTechNbaEnum::getTeamId('BOS'),
                'short_name' => 'BOS', 'city' => 'Boston', 'name' => 'Celtics'
            ],
            [
                'external_id' => NbaTeamExternalDataEnum::BKN->value,
                'market_id' => DigitalSportsTechNbaEnum::getTeamId('BKN'),
                'short_name' => 'BKN', 'city' => 'Brooklyn', 'name' => 'Nets'
            ],
            [
                'external_id' => NbaTeamExternalDataEnum::NYK->value,
                'market_id' => DigitalSportsTechNbaEnum::getTeamId('NYK'),
                'short_name' => 'NYK', 'city' => 'New York', 'name' => 'Knicks'
            ],
            [
                'external_id' => NbaTeamExternalDataEnum::PHI->value,
                'market_id' => DigitalSportsTechNbaEnum::getTeamId('PHI'),
                'short_name' => 'PHI', 'city' => 'Philadelphia', 'name' => '76ers'
            ],
            [
                'external_id' => NbaTeamExternalDataEnum::TOR->value,
                'market_id' => DigitalSportsTechNbaEnum::getTeamId('TOR'),
                'short_name' => 'TOR', 'city' => 'Toronto', 'name' => 'Raptors'
            ],
            [
                'external_id' => NbaTeamExternalDataEnum::CHI->value,
                'market_id' => DigitalSportsTechNbaEnum::getTeamId('CHI'),
                'short_name' => 'CHI', 'city' => 'Chicago', 'name' => 'Bulls'
            ],
            [
                'external_id' => NbaTeamExternalDataEnum::CLE->value,
                'market_id' => DigitalSportsTechNbaEnum::getTeamId('CLE'),
                'short_name' => 'CLE', 'city' => 'Cleveland', 'name' => 'Cavaliers'
            ],
            [
                'external_id' => NbaTeamExternalDataEnum::DET->value,
                'market_id' => DigitalSportsTechNbaEnum::getTeamId('DET'),
                'short_name' => 'DET', 'city' => 'Detroit', 'name' => 'Pistons'
            ],
            [
                'external_id' => NbaTeamExternalDataEnum::IND->value,
                'market_id' => DigitalSportsTechNbaEnum::getTeamId('IND'),
                'short_name' => 'IND', 'city' => 'Indiana', 'name' => 'Pacers'
            ],
            [
                'external_id' => NbaTeamExternalDataEnum::MIL->value,
                'market_id' => DigitalSportsTechNbaEnum::getTeamId('MIL'),
                'short_name' => 'MIL', 'city' => 'Milwaukee', 'name' => 'Bucks'
            ],
            [
                'external_id' => NbaTeamExternalDataEnum::ATL->value,
                'market_id' => DigitalSportsTechNbaEnum::getTeamId('ATL'),
                'short_name' => 'ATL', 'city' => 'Atlanta', 'name' => 'Hawks'
            ],
            [
                'external_id' => NbaTeamExternalDataEnum::CHA->value,
                'market_id' => DigitalSportsTechNbaEnum::getTeamId('CHA'),
                'short_name' => 'CHA', 'city' => 'Charlotte', 'name' => 'Hornets'
            ],
            [
                'external_id' => NbaTeamExternalDataEnum::MIA->value,
                'market_id' => DigitalSportsTechNbaEnum::getTeamId('MIA'),
                'short_name' => 'MIA', 'city' => 'Miami', 'name' => 'Heat'
            ],
            [
                'external_id' => NbaTeamExternalDataEnum::ORL->value,
                'market_id' => DigitalSportsTechNbaEnum::getTeamId('ORL'),
                'short_name' => 'ORL', 'city' => 'Orlando', 'name' => 'Magic'
            ],
            [
                'external_id' => NbaTeamExternalDataEnum::WAS->value,
                'market_id' => DigitalSportsTechNbaEnum::getTeamId('WAS'),
                'short_name' => 'WAS', 'city' => 'Washington', 'name' => 'Wizards'
            ],
            [
                'external_id' => NbaTeamExternalDataEnum::DEN->value,
                'market_id' => DigitalSportsTechNbaEnum::getTeamId('DEN'),
                'short_name' => 'DEN', 'city' => 'Denver', 'name' => 'Nuggets'
            ],
            [
                'external_id' => NbaTeamExternalDataEnum::MIN->value,
                'market_id' => DigitalSportsTechNbaEnum::getTeamId('MIN'),
                'short_name' => 'MIN', 'city' => 'Minnesota', 'name' => 'Timberwolves'
            ],
            [
                'external_id' => NbaTeamExternalDataEnum::OKC->value,
                'market_id' => DigitalSportsTechNbaEnum::getTeamId('OKC'),
                'short_name' => 'OKC', 'city' => 'Oklahoma City', 'name' => 'Thunder'
            ],
            [
                'external_id' => NbaTeamExternalDataEnum::POR->value,
                'market_id' => DigitalSportsTechNbaEnum::getTeamId('POR'),
                'short_name' => 'POR', 'city' => 'Portland', 'name' => 'Trail Blazers'
            ],
            [
                'external_id' => NbaTeamExternalDataEnum::UTA->value,
                'market_id' => DigitalSportsTechNbaEnum::getTeamId('UTA'),
                'short_name' => 'UTA', 'city' => 'Utah', 'name' => 'Jazz'
            ],
            [
                'external_id' => NbaTeamExternalDataEnum::GSW->value,
                'market_id' => DigitalSportsTechNbaEnum::getTeamId('GSW'),
                'short_name' => 'GSW', 'city' => 'Golden State', 'name' => 'Warriors'
            ],
            [
                'external_id' => NbaTeamExternalDataEnum::LAC->value,
                'market_id' => DigitalSportsTechNbaEnum::getTeamId('LAC'),
                'short_name' => 'LAC', 'city' => 'Los Angeles', 'name' => 'Clippers'
            ],
            [
                'external_id' => NbaTeamExternalDataEnum::LAL->value,
                'market_id' => DigitalSportsTechNbaEnum::getTeamId('LAL'),
                'short_name' => 'LAL', 'city' => 'Los Angeles', 'name' => 'Lakers'
            ],
            [
                'external_id' => NbaTeamExternalDataEnum::PHX->value,
                'market_id' => DigitalSportsTechNbaEnum::getTeamId('PHX'),
                'short_name' => 'PHX', 'city' => 'Phoenix', 'name' => 'Suns'
            ],
            [
                'external_id' => NbaTeamExternalDataEnum::SAC->value,
                'market_id' => DigitalSportsTechNbaEnum::getTeamId('SAC'),
                'short_name' => 'SAC', 'city' => 'Sacramento', 'name' => 'Kings'
            ],
            [
                'external_id' => NbaTeamExternalDataEnum::DAL->value,
                'market_id' => DigitalSportsTechNbaEnum::getTeamId('DAL'),
                'short_name' => 'DAL', 'city' => 'Dallas', 'name' => 'Mavericks'
            ],
            [
                'external_id' => NbaTeamExternalDataEnum::HOU->value,
                'market_id' => DigitalSportsTechNbaEnum::getTeamId('HOU'),
                'short_name' => 'HOU', 'city' => 'Houston', 'name' => 'Rockets'
            ],
            [
                'external_id' => NbaTeamExternalDataEnum::MEM->value,
                'market_id' => DigitalSportsTechNbaEnum::getTeamId('MEM'),
                'short_name' => 'MEM', 'city' => 'Memphis', 'name' => 'Grizzlies'
            ],
            [
                'external_id' => NbaTeamExternalDataEnum::NOP->value,
                'market_id' => DigitalSportsTechNbaEnum::getTeamId('NOP'),
                'short_name' => 'NOP', 'city' => 'New Orleans', 'name' => 'Pelicans'
            ],
            [
                'external_id' => NbaTeamExternalDataEnum::SAS->value,
                'market_id' => DigitalSportsTechNbaEnum::getTeamId('SAS'),
                'short_name' => 'SAS', 'city' => 'San Antonio', 'name' => 'Spurs'
            ],
        ];

        NbaTeam::insert($teams);

        $teams = NbaTeam::all();

        $players = [];

        foreach (NbaStatsService::getPlayers() as $playerData) {
            $players[] = [
                'external_id' => $playerData['external_id'],
                'first_name' => transliterator_transliterate('Any-Latin; Latin-ASCII', $playerData['first_name']),
                'last_name' => transliterator_transliterate('Any-Latin; Latin-ASCII', $playerData['last_name']),
                'team_id' => $teams->firstWhere('external_id', $playerData['team_external_id'])?->id,
            ];
        }

        NbaPlayer::insert($players);

        $this->digitalSportsTechService->syncNbaPlayerMarketIds();
    }
}
