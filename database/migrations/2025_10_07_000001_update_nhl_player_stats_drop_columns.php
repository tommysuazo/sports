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
        Schema::table('nhl_player_stats', function (Blueprint $table) {
            $table->dropColumn(['blocked_shots', 'hits', 'goals_against']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nhl_player_stats', function (Blueprint $table) {
            $table->unsignedTinyInteger('blocked_shots')->default(0);
            $table->unsignedTinyInteger('hits')->default(0);
            $table->unsignedTinyInteger('goals_against')->default(0);
        });
    }
};
