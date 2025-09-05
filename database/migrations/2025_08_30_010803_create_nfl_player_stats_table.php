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
        Schema::create('nfl_player_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('nfl_games');
            $table->foreignId('player_id')->constrained('nfl_players');
            $table->foreignId('team_id')->constrained('nfl_teams');

            // Ofensiva
            $table->integer('total_yards')->nullable();
            $table->integer('passing_yards')->nullable();
            $table->integer('pass_completions')->nullable();
            $table->integer('pass_attempts')->nullable();
            $table->integer('receiving_yards')->nullable();
            $table->integer('receptions')->nullable();
            $table->integer('receiving_targets')->nullable();
            $table->integer('rushing_yards')->nullable();
            $table->integer('carries')->nullable();

            // Defensa
            $table->integer('sacks')->nullable();
            $table->integer('tackles')->nullable();

            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('nfl_player_stats');
    }
};
