<?php

use App\Enums\Games\NbaGameStatus;
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
        Schema::create('nba_games', function (Blueprint $table) {
            $table->id();
            // $table->string('sportsnet_id')->unique();
            $table->string('external_id')->unique();
            $table->string('market_id')->unique();
            $table->foreignId('away_team_id')->constrained('nba_teams');
            $table->foreignId('home_team_id')->constrained('nba_teams');
            $table->foreignId('winner_team_id')->nullable()->constrained('nhl_teams');
            $table->timestamp('start_at');
            $table->boolean('is_completed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nba_games');
    }
};
