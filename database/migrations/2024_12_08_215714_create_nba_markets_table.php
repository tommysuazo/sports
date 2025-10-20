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
            $table->foreignId('favorite_team_id')->constrained('nba_teams');
            $table->unsignedTinyInteger('handicap');
            $table->unsignedTinyInteger('points');
            $table->unsignedTinyInteger('first_half_handicap')->nullable();
            $table->unsignedTinyInteger('first_half_points')->nullable();
            $table->unsignedTinyInteger('first_quarter_points')->nullable();
            $table->unsignedTinyInteger('second_quarter_points')->nullable();
            $table->unsignedTinyInteger('third_quarter_points')->nullable();
            $table->unsignedTinyInteger('fourth_quarter_points')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nba_markets');
    }
};
