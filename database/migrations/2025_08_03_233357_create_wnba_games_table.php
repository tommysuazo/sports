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
        Schema::create('wnba_games', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique();
            $table->foreignId('away_team_id')->constrained('wnba_teams');
            $table->foreignId('home_team_id')->constrained('wnba_teams');
            $table->timestamp('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wnba_games');
    }
};
