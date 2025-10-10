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
        Schema::create('nhl_player_markets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('nhl_games');
            $table->foreignId('player_id')->constrained('nhl_players');
            $table->decimal('goals', 5, 1)->nullable();
            $table->decimal('shots', 5, 1)->nullable();
            $table->decimal('assists', 5, 1)->nullable();
            $table->decimal('points', 5, 1)->nullable();
            $table->decimal('saves', 5, 1)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nhl_player_markets');
    }
};
