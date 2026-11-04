<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_id')->constrained('sports');
            $table->string('name', 80);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['sport_id', 'name']);
            $table->index(['sport_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
