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
        Schema::create('nba_player_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('nba_players');
            $table->foreignId('score_id')->constrained('nba_scores');
            $table->integer('mins')->default(0);
            $table->integer('points')->default(0);
            $table->integer('assists')->default(0);
            $table->integer('rebounds')->default(0);
            $table->integer('steals')->default(0);
            $table->integer('blocks')->default(0);
            $table->integer('turnovers')->default(0);
            $table->integer('fouls')->default(0);
            $table->integer('field_goals_made')->default(0);
            $table->integer('field_goals_attempted')->default(0);
            $table->integer('three_pointers_made')->default(0);
            $table->integer('three_pointers_attempted')->default(0);
            $table->integer('free_throws_made')->default(0);
            $table->integer('free_throws_attempted')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nba_player_scores');
    }
};
