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
        Schema::create('nba_player_markets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('favorite_team_id')->constrained('nba_teams');
            $table->decimal('points', 5, 1)->nullable();
            $table->decimal('assists', 5, 1)->nullable();
            $table->decimal('rebounds', 5, 1)->nullable();
            $table->decimal('pt3', 5, 1)->nullable();
            $table->decimal('pra', 5, 1)->nullable();
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
