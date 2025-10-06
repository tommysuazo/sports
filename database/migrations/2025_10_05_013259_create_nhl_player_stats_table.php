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
        Schema::create('nhl_player_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('nhl_games');
            $table->foreignId('player_id')->constrained('nhl_players');
            $table->boolean('is_starter');
            $table->unsignedTinyInteger('time');
            $table->unsignedTinyInteger('goals')->default(0);
            $table->unsignedTinyInteger('shots')->default(0);
            $table->unsignedTinyInteger('assists')->default(0);
            $table->unsignedTinyInteger('points')->default(0);
            $table->unsignedTinyInteger('hits')->default(0);
            $table->unsignedTinyInteger('blocked_shots')->default(0);
            $table->unsignedTinyInteger('saves')->default(0);
            $table->unsignedTinyInteger('goals_against')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nhl_player_stats');
    }
};
