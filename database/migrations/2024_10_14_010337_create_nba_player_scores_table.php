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
        Schema::create('nba_player_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('nba_games');
            $table->foreignId('team_id')->constrained('nba_teams');
            $table->foreignId('player_id')->constrained('nba_players');
            $table->boolean('is_away');
            $table->boolean('is_starter');
            $table->string('mins', 6);
            $table->unsignedTinyInteger('points');
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
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nba_player_scores');
    }
};
