<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wnba_player_markets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->nullable()->constrained('wnba_games')->nullOnDelete();
            $table->foreignId('player_id')->constrained('wnba_players')->cascadeOnDelete();
            $table->decimal('points', 5, 1)->nullable();
            $table->decimal('assists', 5, 1)->nullable();
            $table->decimal('rebounds', 5, 1)->nullable();
            $table->decimal('pt3', 5, 1)->nullable();
            $table->decimal('pra', 5, 1)->nullable();
            $table->timestamps();
            $table->unique(['game_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wnba_player_markets');
    }
};
