<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TIZ-20: tabla de posiciones de una competencia, por categoría. Se carga a mano: el club no juega
 * todos los partidos del torneo, así que no se puede calcular desde `matches`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_standings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained('competitions')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories');
            $table->unsignedSmallInteger('position');
            $table->string('team_name', 120);
            // La fila del club, para resaltarla.
            $table->boolean('is_own')->default(false);
            $table->unsignedSmallInteger('played')->default(0);
            $table->unsignedSmallInteger('won')->default(0);
            $table->unsignedSmallInteger('drawn')->default(0);
            $table->unsignedSmallInteger('lost')->default(0);
            $table->unsignedSmallInteger('goals_for')->default(0);
            $table->unsignedSmallInteger('goals_against')->default(0);
            $table->smallInteger('points')->default(0);
            $table->timestamps();

            $table->unique(['competition_id', 'category_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_standings');
    }
};
