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
        Schema::create('nfl_games', function (Blueprint $table) {
            $table->id();
            $table->string('external_id');
            $table->string('market_id')->nullable();
            $table->integer('season');
            $table->integer('week');
            $table->date('played_at');
            $table->boolean('is_completed');
            $table->foreignId('away_team_id')->constrained('nfl_teams');
            $table->foreignId('home_team_id')->constrained('nfl_teams');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nfl_games');
    }
};
