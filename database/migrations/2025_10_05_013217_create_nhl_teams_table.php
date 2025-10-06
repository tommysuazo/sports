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
        Schema::create('nhl_teams', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->nullable()->unique();
            $table->string('market_id')->nullable()->unique();
            $table->string('code', 50)->unique()->index();
            $table->string('name');
            $table->string('city');
            $table->unsignedTinyInteger('wins')->default(0);
            $table->unsignedTinyInteger('loses')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nhl_teams');
    }
};
