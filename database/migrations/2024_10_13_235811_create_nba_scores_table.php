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
        Schema::create('nba_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('nba_teams');
            $table->integer('points')->default(0);
            $table->integer('first_half_points')->default(0);
            $table->integer('second_half_points')->default(0);
            $table->integer('first_quarter_points')->default(0);
            $table->integer('second_quarter_points')->default(0);
            $table->integer('third_quarter_points')->default(0);
            $table->integer('fourth_quarter_points')->default(0);
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
        Schema::dropIfExists('nba_scores');
    }
};
