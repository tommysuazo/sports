<?php

namespace Database\Seeders;

use App\Enums\DigitalSportsTech\DigitalSportsTechLeagueEnum;
use App\Enums\DigitalSportsTech\DigitalSportsTechNbaEnum;
use App\Enums\Leagues\LeagueEnum;
use App\Models\NbaPlayer;
use App\Models\NbaTeam;
use App\Services\DigitalSportsTechService;
use App\Services\SportsnetService;
use Exception;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class NbaSportsNetSeeder extends Seeder
{
    public function __construct(
        protected DigitalSportsTechService $digitalSportsTechService,
        protected SportsnetService $sportsnetService
    ) {
    }


    public function run(): void
    {
        $data = $this->getDataFromExternalApi();

        $teams = collect($data['teams']);

        $rosters = collect($data['rosters']);

        $insertion = [];

        foreach ($teams as $team) {
            $insertion[] = [
                'external_id' => $team['id'],
                'market_id' => DigitalSportsTechNbaEnum::getTeamId($team['short_name']),
                'name' => $team['name'],
                'short_name' => $team['short_name'],
                'city' => $team['city'],
            ];
        }

        NbaTeam::insert($insertion);

        $nbaTeams = NbaTeam::all();

        $insertion = [];

        foreach ($rosters as $roster) {
            foreach ($roster as $player) {
                $insertion[] = [
                    'external_id' => $player['id'],
                    'first_name' => transliterator_transliterate('Any-Latin; Latin-ASCII', $player['first_name']),
                    'last_name' => transliterator_transliterate('Any-Latin; Latin-ASCII', $player['last_name']),
                    'position' => $player['position'],
                    'team_id' => $nbaTeams->firstWhere('external_id', $player['current_team']['id'])->id,
                ];
            }
        }

        NbaPlayer::insert($insertion);

        $this->digitalSportsTechService->syncNbaPlayerMarketIds();
    }

    private function getDataFromSecundaryTable()
    {
        $connection = 'sports_seeders';

        $nba = DB::connection($connection)->table('leagues')->where('code', LeagueEnum::NBA->value)->first();
        
        $rosters = DB::connection($connection)->table('rosters')->where('league', LeagueEnum::NBA->value)->get();

        $teams = json_decode($nba->teams_data);

        return [
            'rosters' => $rosters,
            'teams' => $teams,
        ];
    }

    private function getDataFromExternalApi()
    {
        $league = LeagueEnum::NBA->value;

        $response = Http::get($this->sportsnetService->getTeamsUrl($league));

        if (!$response->successful()) {
            throw new Exception("Failed to get nba teams.");
        }

        $teams = $response->json('data');

        $rosters = [];

        foreach ($teams as $team) {
            $response = Http::get($this->sportsnetService->getTeamPlayersUrl($league, $team['id']));

            if (!$response->successful()) {
                throw new Exception("Failed to get {$league} teams.");
            }

            $rosters[] = $response->json('data');
        }

        return ["teams" => $teams, "rosters" => $rosters];
    }
}
