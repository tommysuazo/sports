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
        Schema::create('ncaab_teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('external_id')->unique();
            $table->string('market_id')->nullable()->unique();
            $table->string('short_name', 5);
            $table->string('city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ncaab_teams');
    }
};
