<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TIZ-31: papeles que pide cada competencia para habilitar a un jugador. Cambian según el deporte
 * y la liga: los configura el club.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained('competitions')->cascadeOnDelete();
            $table->string('name', 120);
            // Meses de validez (el apto médico, 12). Null = no vence.
            $table->unsignedSmallInteger('validity_months')->nullable();
            $table->boolean('requires_file')->default(false);
            // Se pide solo a menores de 18 (se calcula con players.birth_date).
            $table->boolean('minors_only')->default(false);
            $table->boolean('is_required')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_requirements');
    }
};
