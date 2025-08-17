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
        Schema::create('ncaab_players', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique();
            $table->string('market_id')->nullable()->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->foreignId('team_id')->nullable()->constrained('ncaab_teams');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ncaab_players');
    }
};
