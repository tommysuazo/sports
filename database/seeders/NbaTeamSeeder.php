<?php

namespace Database\Seeders;

use App\Models\NbaTeam;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NbaTeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teams = [];

        $teams[] = ["code" => "BOS", "name" => "Celtics", "city" => "Boston"];
        $teams[] = ["code" => "BKN", "name" => "Nets", "city" => "Brooklyn"];
        $teams[] = ["code" => "NYK", "name" => "Knicks", "city" => "New York"];
        $teams[] = ["code" => "PHI", "name" => "76ers", "city" => "Philadelphia"];
        $teams[] = ["code" => "TOR", "name" => "Raptors", "city" => "Toronto"];
        
        $teams[] = ["code" => "CHI", "name" => "Bulls", "city" => "Chicago"];
        $teams[] = ["code" => "CLE", "name" => "Cavaliers", "city" => "Cleveland"];
        $teams[] = ["code" => "DET", "name" => "Pistons", "city" => "Detroit"];
        $teams[] = ["code" => "IND", "name" => "Pacers", "city" => "Indiana"];
        $teams[] = ["code" => "MIL", "name" => "Bucks", "city" => "Milwaukee"];

        $teams[] = ["code" => "ATL", "name" => "Hawks", "city" => "Atlanta"];
        $teams[] = ["code" => "CHA", "name" => "Hornets", "city" => "Charlotte"];
        $teams[] = ["code" => "MIA", "name" => "Heat", "city" => "Miami"];
        $teams[] = ["code" => "ORL", "name" => "Magic", "city" => "Orlando"];
        $teams[] = ["code" => "WAS", "name" => "Wizards", "city" => "Washington"];

        $teams[] = ["code" => "DEN", "name" => "Nuggets", "city" => "Denver"];
        $teams[] = ["code" => "MIN", "name" => "Timberwolves", "city" => "Minnesota"];
        $teams[] = ["code" => "OKC", "name" => "Thunder", "city" => "Oklahoma City"];
        $teams[] = ["code" => "POR", "name" => "Trail Blazers", "city" => "Portland"];
        $teams[] = ["code" => "UTA", "name" => "Jazz", "city" => "Utah"];

        $teams[] = ["code" => "GSW", "name" => "Warriors", "city" => "Golden State"];
        $teams[] = ["code" => "LAC", "name" => "Clippers", "city" => "Los Angeles"];
        $teams[] = ["code" => "LAL", "name" => "Lakers", "city" => "Los Angeles"];
        $teams[] = ["code" => "PHX", "name" => "Suns", "city" => "Phoenix"];
        $teams[] = ["code" => "SAC", "name" => "Kings", "city" => "Sacramento"];

        $teams[] = ["code" => "DAL", "name" => "Mavericks", "city" => "Dallas"];
        $teams[] = ["code" => "HOU", "name" => "Rockets", "city" => "Houston"];
        $teams[] = ["code" => "MEM", "name" => "Grizzlies", "city" => "Memphis"];
        $teams[] = ["code" => "NOP", "name" => "Pelicans", "city" => "New Orleans"];
        $teams[] = ["code" => "SAS", "name" => "Spurs", "city" => "San Antonio"];

        NbaTeam::insert($teams);
    }
}
