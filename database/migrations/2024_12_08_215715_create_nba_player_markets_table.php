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
            $table->unsignedTinyInteger('points')->nullable();
            $table->unsignedTinyInteger('assists')->nullable();
            $table->unsignedTinyInteger('rebounds')->nullable();
            $table->unsignedTinyInteger('pt3')->nullable();
            $table->unsignedTinyInteger('pra')->nullable();
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
