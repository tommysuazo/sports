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
        Schema::create('nhl_team_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('nhl_games');
            $table->foreignId('team_id')->constrained('nhl_teams');
            $table->unsignedTinyInteger('goals');
            $table->unsignedTinyInteger('shots');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nhl_team_stats');
    }
};
