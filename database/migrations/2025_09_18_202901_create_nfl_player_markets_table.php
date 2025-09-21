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
        Schema::create('nfl_player_markets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('nfl_games');
            $table->foreignId('player_id')->constrained('nfl_players');

            // Ofensiva
            $table->decimal('total_yards', 5, 1)->nullable();
            $table->decimal('passing_yards', 5, 1)->nullable();
            $table->decimal('pass_completions', 5, 1)->nullable();
            $table->decimal('pass_attempts', 5, 1)->nullable();
            $table->decimal('receiving_yards', 5, 1)->nullable();
            $table->decimal('receptions', 5, 1)->nullable();
            $table->decimal('receiving_targets', 5, 1)->nullable();
            $table->decimal('rushing_yards', 5, 1)->nullable();
            $table->decimal('carries', 5, 1)->nullable();

            // Defensa
            $table->decimal('sacks', 5, 1)->nullable();
            $table->decimal('tackles', 5, 1)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nfl_player_markets');
    }
};
