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
        Schema::create('nba_markets', function (Blueprint $table) {
            $table->id();
            $table->integer('points');
            $table->integer('first_half_points');
            $table->integer('second_half_points');
            $table->integer('first_quarter_points');
            $table->integer('second_quarter_points');
            $table->integer('third_quarter_points');
            $table->integer('fourth_quarter_points');
            $table->integer('assists');
            $table->integer('rebounds');
            $table->integer('steals');
            $table->integer('blocks');
            $table->integer('turnovers');
            $table->integer('fouls');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nba_markets');
    }
};
