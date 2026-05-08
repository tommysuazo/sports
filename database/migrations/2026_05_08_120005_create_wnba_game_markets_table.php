<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wnba_game_markets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('wnba_games');
            $table->foreignId('favorite_team_id')->constrained('wnba_teams');
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

    public function down(): void
    {
        Schema::dropIfExists('wnba_game_markets');
    }
};
