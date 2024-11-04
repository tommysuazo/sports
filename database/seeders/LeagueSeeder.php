<?php

namespace Database\Seeders;

use App\Enums\LeagueEnum;
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
        $insertion = [];

        foreach (LeagueEnum::getFullName() as $league => $fullName) {
            $insertion[] = ['name' => $fullName, 'short_name' => $league];
        }

        League::insert($insertion);
    }
}
