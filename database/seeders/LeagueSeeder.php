<?php

namespace Database\Seeders;

use App\Models\League;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LeagueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        League::create([
            "name" => "National Basketball Association",
            "short_name" => "NBA",
        ]);

        League::create([
            "name" => "National Football League",
            "short_name" => "NFL",
        ]);

        League::create([
            "name" => "NCAA Basketball",
            "short_name" => "NCAAB",
        ]);
    }
}
