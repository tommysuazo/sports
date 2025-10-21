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
        Schema::create('nba_game_markets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('nba_games');
            $table->foreignId('favorite_team_id')->constrained('nba_teams');
            $table->decimal('handicap', 5, 1);
            $table->decimal('points', 5, 1);
            $table->decimal('first_half_handicap', 5, 1)->nullable();
            $table->decimal('first_half_points', 5, 1)->nullable();
            $table->decimal('first_quarter_points', 5, 1)->nullable();
            $table->decimal('second_quarter_points', 5, 1)->nullable();
            $table->decimal('third_quarter_points', 5, 1)->nullable();
            $table->decimal('fourth_quarter_points', 5, 1)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nba_game_markets');
    }
};
