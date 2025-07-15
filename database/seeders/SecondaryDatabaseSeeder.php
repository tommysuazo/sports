<?php

namespace Database\Seeders;

use App\Enums\Leagues\LeagueEnum;
use App\Services\DigitalSportsTechService;
use App\Services\SportsnetService;
use Exception;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class SecondaryDatabaseSeeder extends Seeder
{
    public function __construct(
        protected SportsnetService $sportsnetService,
        protected DigitalSportsTechService $digitalSportsTechService,
    ){
    }
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $connection = 'sports_seeders';

        $tables = DB::connection($connection)->select('SHOW TABLES');
        
        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            Schema::connection($connection)->dropIfExists($tableName);
        }

        DB::connection($connection)->statement("
            CREATE TABLE IF NOT EXISTS leagues (
                code VARCHAR(32) PRIMARY KEY,
                teams_url VARCHAR(255),
                teams_data JSON
            )
        ");

        $leaguesInsertion = [];
        

        foreach (LeagueEnum::getSupportedBySportsnet() as $league) {
            $json = null;

            $leagueTeamsUrl = $this->sportsnetService->getTeamsUrl($league);

            $response = Http::get($leagueTeamsUrl);

            if (!$response->successful()) {
                throw new Exception("Failed to get {$league} teams.");
            }

            $json = $response->json('data');
        

            $leaguesInsertion[$league] = [
                'code' => $league,
                'teams_url' => $leagueTeamsUrl,
                'teams_data' => json_encode($json),
            ];
        }
        
        DB::connection($connection)->table('leagues')->insert($leaguesInsertion);

        // ----------------------------------------------------

        DB::connection($connection)->statement("
            CREATE TABLE IF NOT EXISTS rosters (
                team_external_id VARCHAR(255) NOT NULL,
                team_name VARCHAR(255) NOT NULL,
                league VARCHAR(32) NOT NULL,
                players_url VARCHAR(255) NOT NULL,
                players_data JSON NOT NULL
            )
        ");

        $rostersInsertion = [];

        foreach ($leaguesInsertion as $league => $data) {

            $teams = json_decode($data['teams_data']);

            foreach ($teams as $team) {

                $url = $this->sportsnetService->getTeamPlayersUrl($league, $team->id);

                $response = Http::get($url);

                if (!$response->successful()) {
                    throw new Exception("Failed to get {$league} teams.");
                }

                $json = $response->json('data');

                $rostersInsertion[] = [
                    'team_external_id' => $team->id,
                    'team_name' => $team->team_name_formatted,
                    'league' => $league,
                    'players_url' => $url,
                    'players_data' => json_encode($json),
                ];
            }
        }

        DB::connection($connection)->table('rosters')->insert($rostersInsertion);


        DB::connection($connection)->statement("
            CREATE TABLE IF NOT EXISTS nba_games (
                external_id VARCHAR(255) PRIMARY KEY,
                started_at DATETIME NOT NULL,
                `data` JSON NOT NULL
            );
        ");

        DB::connection($connection)->statement("
            CREATE TABLE IF NOT EXISTS nfl_games (
                external_id VARCHAR(255) PRIMARY KEY,
                started_at DATETIME NOT NULL,
                `data` JSON NOT NULL
            );
        ");
        
        DB::connection($connection)->statement("
            CREATE TABLE IF NOT EXISTS nhl_games (
                external_id VARCHAR(255) PRIMARY KEY,
                started_at DATETIME NOT NULL,
                `data` JSON NOT NULL
            );
        ");


        DB::connection($connection)->statement("
            CREATE TABLE IF NOT EXISTS players_market_id (
                external_id VARCHAR(255),
                market_id VARCHAR(255),
                first_name VARCHAR(255),
                last_name VARCHAR(255),
                league VARCHAR(32)
            );
        ");

        foreach ($teams as $team) {

            $url = $this->sportsnetService->getTeamPlayersUrl($league, $team->id);

            $response = Http::get($url);

            if (!$response->successful()) {
                throw new Exception("Failed to get {$league} teams.");
            }

            $json = $response->json('data');

            $rostersInsertion[] = [
                'team_external_id' => $team->id,
                'team_name' => $team->team_name_formatted,
                'league' => $league,
                'players_url' => $url,
                'players_data' => json_encode($json),
            ];
        }

    }
}
