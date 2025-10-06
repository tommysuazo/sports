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
        Schema::create('nhl_injuries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('nhl_games');
            $table->foreignId('player_id')->constrained('nhl_players');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nhl_injuries');
    }
};
