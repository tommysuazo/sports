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
        Schema::create('nba_players', function (Blueprint $table) {
            $table->id();
            // $table->string('sportsnet_id')->unique();
            $table->string('external_id')->unique();
            $table->string('market_id')->nullable()->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->foreignId('team_id')->nullable()->constrained('nba_teams');
            $table->decimal('points_market', 4, 1)->nullable();
            $table->decimal('assists_market', 4, 1)->nullable();
            $table->decimal('rebounds_market', 4, 1)->nullable();
            $table->decimal('pt3_market', 4, 1)->nullable();
            $table->decimal('pra_market', 4, 1)->nullable();
            $table->decimal('steals_market', 4, 1)->nullable();
            $table->decimal('blocks_market', 4, 1)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nba_players');
    }
};
