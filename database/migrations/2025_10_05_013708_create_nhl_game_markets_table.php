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
        Schema::create('nhl_game_markets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->unique()->constrained('nhl_games');
            $table->foreignId('favorite_team_id')->constrained('nhl_teams');
            $table->decimal('handicap', 5, 1)->nullable();
            $table->decimal('total_points', 5, 1)->nullable();
            $table->decimal('away_team_solo_points', 5, 1)->nullable();
            $table->decimal('home_team_solo_points', 5, 1)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nhl_game_markets');
    }
};
