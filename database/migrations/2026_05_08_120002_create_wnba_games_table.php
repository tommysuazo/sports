<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wnba_games', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique();
            $table->string('market_id')->nullable()->unique();
            $table->foreignId('away_team_id')->constrained('wnba_teams');
            $table->foreignId('home_team_id')->constrained('wnba_teams');
            $table->foreignId('winner_team_id')->nullable()->constrained('wnba_teams');
            $table->timestamp('start_at');
            $table->boolean('is_completed');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wnba_games');
    }
};
