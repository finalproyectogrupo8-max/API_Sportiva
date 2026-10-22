<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_id')->constrained('sports');
            $table->foreignId('season_id')->constrained('seasons');
            $table->string('name', 80);
            $table->smallInteger('min_age')->nullable();
            $table->smallInteger('max_age')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['sport_id', 'season_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
