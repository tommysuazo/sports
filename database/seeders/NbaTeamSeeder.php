<?php

namespace Database\Seeders;

use App\Enums\LeagueEnum;
use App\Models\NbaPlayer;
use App\Models\NbaTeam;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NbaTeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teams = [];

        $connection = 'sports_seeders';

        $nba = DB::connection($connection)->table('leagues')->where('code', LeagueEnum::NBA->value)->first();
        
        $rosters = DB::connection($connection)->table('rosters')->where('league', LeagueEnum::NBA->value)->get();

        $teams = json_decode($nba->teams_data);

        $insertion = [];

        foreach ($teams as $team) {
            $insertion[] = [
                'sportsnet_id' => $team->id,
                'name' => $team->name,
                'short_name' => $team->short_name,
                'city' => $team->city,
            ];
        }

        NbaTeam::insert($insertion);

        $nbaTeams = NbaTeam::all();

        $insertion = [];

        

        foreach ($rosters as $roster) {
            $players = json_decode($roster->players_data);

            foreach ($players as $player) {
                $insertion[] = [
                    'sportsnet_id' => $player->id,
                    'first_name' => $player->first_name,
                    'last_name' => $player->last_name,
                    'position' => $player->position,
                    'team_id' => $nbaTeams->firstWhere('sportsnet_id', $player->current_team->id)->id,
                ];
            }
        }

        NbaPlayer::insert($insertion);
    }
}
