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
        Schema::create('nba_team_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('nba_games');
            $table->foreignId('team_id')->constrained('nba_teams');
            $table->boolean('is_away');
            $table->unsignedSmallInteger('points');
            $table->unsignedTinyInteger('first_half_points');
            $table->unsignedTinyInteger('second_half_points');
            $table->unsignedTinyInteger('first_quarter_points');
            $table->unsignedTinyInteger('second_quarter_points');
            $table->unsignedTinyInteger('third_quarter_points');
            $table->unsignedTinyInteger('fourth_quarter_points');
            $table->unsignedTinyInteger('overtimes');
            $table->unsignedSmallInteger('overtime_points');
            $table->unsignedTinyInteger('assists');
            $table->unsignedTinyInteger('rebounds');
            $table->unsignedTinyInteger('steals');
            $table->unsignedTinyInteger('blocks');
            $table->unsignedTinyInteger('turnovers');
            $table->unsignedTinyInteger('fouls');
            $table->unsignedTinyInteger('field_goals_made');
            $table->unsignedTinyInteger('field_goals_attempted');
            $table->unsignedTinyInteger('three_pointers_made');
            $table->unsignedTinyInteger('three_pointers_attempted');
            $table->unsignedTinyInteger('free_throws_made');
            $table->unsignedTinyInteger('free_throws_attempted');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nba_team_stats');
    }
};
