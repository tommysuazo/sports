<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wnba_teams', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique();
            $table->string('market_id')->nullable()->unique();
            $table->string('name');
            $table->string('short_name', 5);
            $table->string('city');
            $table->unsignedTinyInteger('wins')->default(0);
            $table->unsignedTinyInteger('losses')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wnba_teams');
    }
};
