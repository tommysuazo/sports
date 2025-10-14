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
        Schema::create('nhl_games', function (Blueprint $table) {
            $table->id();
            $table->string('external_id');
            $table->string('market_id')->nullable();
            $table->integer('season');
            $table->timestamp('start_at');
            $table->boolean('is_completed');
            $table->foreignId('away_team_id')->constrained('nhl_teams');
            $table->foreignId('home_team_id')->constrained('nhl_teams');
            $table->foreignId('winner_team_id')->nullable()->constrained('nhl_teams');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nhl_games');
    }
};
