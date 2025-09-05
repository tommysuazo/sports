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
        Schema::create('nfl_team_stats', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('game_id')->constrained('nfl_games');
            $table->foreignId('team_id')->constrained('nfl_teams');

            // Puntuación
            $table->integer('points_total')->default(0);
            $table->integer('points_q1')->default(0);
            $table->integer('points_q2')->default(0);
            $table->integer('points_q3')->default(0);
            $table->integer('points_q4')->default(0);
            $table->integer('points_ot')->default(0);

            // Ofensiva
            $table->integer('total_yards')->nullable();
            $table->integer('passing_yards')->nullable();
            $table->integer('pass_completions')->nullable();
            $table->integer('pass_attempts')->nullable();
            $table->integer('rushing_yards')->nullable();
            $table->integer('carries')->nullable();

            // Defensa
            $table->integer('sacks')->nullable();
            $table->integer('tackles')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nfl_team_stats');
    }
};
