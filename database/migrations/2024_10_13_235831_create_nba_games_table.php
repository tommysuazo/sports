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
        Schema::create('nba_games', function (Blueprint $table) {
            $table->id();
            $table->string('sportsnet_id')->nullable()->unique();
            $table->foreignId('away_score_id')->unique()->constrained('nba_scores');
            $table->foreignId('home_score_id')->unique()->constrained('nba_scores');
            $table->timestamp('started_at');
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
