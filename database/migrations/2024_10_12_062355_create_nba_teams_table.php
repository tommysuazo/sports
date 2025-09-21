<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nba_teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // $table->string('sportsnet_id')->unique();
            $table->string('external_id')->unique();
            $table->string('market_id')->nullable()->unique();
            $table->string('short_name', 5);
            $table->string('city');

            
            // $table->decimal('average_points', 5, 1)->default(0);
            // $table->decimal('average_points_against', 5, 1)->default(0);
            // $table->decimal('average_first_half_points', 5, 1)->default(0);
            // $table->decimal('average_first_half_points_against', 5, 1)->default(0);
            // $table->decimal('average_second_half_points', 5, 1)->default(0);
            // $table->decimal('average_second_half_points_against', 5, 1)->default(0);
            // $table->decimal('average_first_quarter_points', 5, 1)->default(0);
            // $table->decimal('average_first_quarter_points_against', 5, 1)->default(0);
            // $table->decimal('average_second_quarter_points', 5, 1)->default(0);
            // $table->decimal('average_second_quarter_points_against', 5, 1)->default(0);
            // $table->decimal('average_third_qeuarter_points', 5, 1)->default(0);
            // $table->decimal('average_third_quarter_points_against', 5, 1)->default(0);
            // $table->decimal('average_fourth_quarter_points', 5, 1)->default(0);
            // $table->decimal('average_fourth_quarter_points_against', 5, 1)->default(0);
            // $table->decimal('average_assists', 5, 1)->default(0);
            // $table->decimal('average_assists_against', 5, 1)->default(0);
            // $table->decimal('average_rebounds', 5, 1)->default(0);
            // $table->decimal('average_rebounds_against', 5, 1)->default(0);
            // $table->decimal('average_steals', 5, 1)->default(0);
            // $table->decimal('average_steals_against', 5, 1)->default(0);
            // $table->decimal('average_blocks', 5, 1)->default(0);
            // $table->decimal('average_blocks_against', 5, 1)->default(0);
            // $table->decimal('average_turnovers', 5, 1)->default(0);
            // $table->decimal('average_turnovers_against', 5, 1)->default(0);
            // $table->decimal('average_fouls', 5, 1)->default(0);
            // $table->decimal('average_fouls_against', 5, 1)->default(0);
            // $table->decimal('average_field_goals_made', 5, 1)->default(0);
            // $table->decimal('average_field_goals_made_against', 5, 1)->default(0);
            // $table->decimal('average_field_goals_attempted', 5, 1)->default(0);
            // $table->decimal('average_field_goals_attempted_against', 5, 1)->default(0);
            // $table->decimal('average_three_pointers_made', 5, 1)->default(0);
            // $table->decimal('average_three_pointers_made_against', 5, 1)->default(0);
            // $table->decimal('average_three_pointers_attempted', 5, 1)->default(0);
            // $table->decimal('average_three_pointers_attempted_against', 5, 1)->default(0);
            // $table->decimal('average_free_throws_made', 5, 1)->default(0);
            // $table->decimal('average_free_throws_made_against', 5, 1)->default(0);
            // $table->decimal('average_free_throws_attempted', 5, 1)->default(0);
            // $table->decimal('average_free_throws_attempted_against', 5, 1)->default(0);

            // $table->decimal('home_average_points', 5, 1)->default(0);
            // $table->decimal('home_average_points_against', 5, 1)->default(0);
            // $table->decimal('home_average_first_half_points', 5, 1)->default(0);
            // $table->decimal('home_average_first_half_points_against', 5, 1)->default(0);
            // $table->decimal('home_average_second_half_points', 5, 1)->default(0);
            // $table->decimal('home_average_second_half_points_against', 5, 1)->default(0);
            // $table->decimal('home_average_first_quarter_points', 5, 1)->default(0);
            // $table->decimal('home_average_first_quarter_points_against', 5, 1)->default(0);
            // $table->decimal('home_average_second_quarter_points', 5, 1)->default(0);
            // $table->decimal('home_average_second_quarter_points_against', 5, 1)->default(0);
            // $table->decimal('home_average_third_qeuarter_points', 5, 1)->default(0);
            // $table->decimal('home_average_third_quarter_points_against', 5, 1)->default(0);
            // $table->decimal('home_average_fourth_quarter_points', 5, 1)->default(0);
            // $table->decimal('home_average_fourth_quarter_points_against', 5, 1)->default(0);
            // $table->decimal('home_average_assists', 5, 1)->default(0);
            // $table->decimal('home_average_assists_against', 5, 1)->default(0);
            // $table->decimal('home_average_rebounds', 5, 1)->default(0);
            // $table->decimal('home_average_rebounds_against', 5, 1)->default(0);
            // $table->decimal('home_average_steals', 5, 1)->default(0);
            // $table->decimal('home_average_steals_against', 5, 1)->default(0);
            // $table->decimal('home_average_blocks', 5, 1)->default(0);
            // $table->decimal('home_average_blocks_against', 5, 1)->default(0);
            // $table->decimal('home_average_turnovers', 5, 1)->default(0);
            // $table->decimal('home_average_turnovers_against', 5, 1)->default(0);
            // $table->decimal('home_average_fouls', 5, 1)->default(0);
            // $table->decimal('home_average_fouls_against', 5, 1)->default(0);
            // $table->decimal('home_average_field_goals_made', 5, 1)->default(0);
            // $table->decimal('home_average_field_goals_made_against', 5, 1)->default(0);
            // $table->decimal('home_average_field_goals_attempted', 5, 1)->default(0);
            // $table->decimal('home_average_field_goals_attempted_against', 5, 1)->default(0);
            // $table->decimal('home_average_three_pointers_made', 5, 1)->default(0);
            // $table->decimal('home_average_three_pointers_made_against', 5, 1)->default(0);
            // $table->decimal('home_average_three_pointers_attempted', 5, 1)->default(0);
            // $table->decimal('home_average_three_pointers_attempted_against', 5, 1)->default(0);
            // $table->decimal('home_average_free_throws_made', 5, 1)->default(0);
            // $table->decimal('home_average_free_throws_made_against', 5, 1)->default(0);
            // $table->decimal('home_average_free_throws_attempted', 5, 1)->default(0);
            // $table->decimal('home_average_free_throws_attempted_against', 5, 1)->default(0);

            // $table->decimal('away_average_points', 5, 1)->default(0);
            // $table->decimal('away_average_points_against', 5, 1)->default(0);
            // $table->decimal('away_average_first_half_points', 5, 1)->default(0);
            // $table->decimal('away_average_first_half_points_against', 5, 1)->default(0);
            // $table->decimal('away_average_second_half_points', 5, 1)->default(0);
            // $table->decimal('away_average_second_half_points_against', 5, 1)->default(0);
            // $table->decimal('away_average_first_quarter_points', 5, 1)->default(0);
            // $table->decimal('away_average_first_quarter_points_against', 5, 1)->default(0);
            // $table->decimal('away_average_second_quarter_points', 5, 1)->default(0);
            // $table->decimal('away_average_second_quarter_points_against', 5, 1)->default(0);
            // $table->decimal('away_average_third_qeuarter_points', 5, 1)->default(0);
            // $table->decimal('away_average_third_quarter_points_against', 5, 1)->default(0);
            // $table->decimal('away_average_fourth_quarter_points', 5, 1)->default(0);
            // $table->decimal('away_average_fourth_quarter_points_against', 5, 1)->default(0);
            // $table->decimal('away_average_assists', 5, 1)->default(0);
            // $table->decimal('away_average_assists_against', 5, 1)->default(0);
            // $table->decimal('away_average_rebounds', 5, 1)->default(0);
            // $table->decimal('away_average_rebounds_against', 5, 1)->default(0);
            // $table->decimal('away_average_steals', 5, 1)->default(0);
            // $table->decimal('away_average_steals_against', 5, 1)->default(0);
            // $table->decimal('away_average_blocks', 5, 1)->default(0);
            // $table->decimal('away_average_blocks_against', 5, 1)->default(0);
            // $table->decimal('away_average_turnovers', 5, 1)->default(0);
            // $table->decimal('away_average_turnovers_against', 5, 1)->default(0);
            // $table->decimal('away_average_fouls', 5, 1)->default(0);
            // $table->decimal('away_average_fouls_against', 5, 1)->default(0);
            // $table->decimal('away_average_field_goals_made', 5, 1)->default(0);
            // $table->decimal('away_average_field_goals_made_against', 5, 1)->default(0);
            // $table->decimal('away_average_field_goals_attempted', 5, 1)->default(0);
            // $table->decimal('away_average_field_goals_attempted_against', 5, 1)->default(0);
            // $table->decimal('away_average_three_pointers_made', 5, 1)->default(0);
            // $table->decimal('away_average_three_pointers_made_against', 5, 1)->default(0);
            // $table->decimal('away_average_three_pointers_attempted', 5, 1)->default(0);
            // $table->decimal('away_average_three_pointers_attempted_against', 5, 1)->default(0);
            // $table->decimal('away_average_free_throws_made', 5, 1)->default(0);
            // $table->decimal('away_average_free_throws_made_against', 5, 1)->default(0);
            // $table->decimal('away_average_free_throws_attempted', 5, 1)->default(0);
            // $table->decimal('away_average_free_throws_attempted_against', 5, 1)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nba_teams');
    }
};
