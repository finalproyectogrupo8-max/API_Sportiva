<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TIZ-31: ligas y torneos del club, qué categorías los juegan y qué papeles pide cada uno para
 * habilitar a un jugador. Los requisitos cambian según el deporte y la liga: los configura el club.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_id')->constrained('sports');
            $table->foreignId('season_id')->constrained('seasons');
            $table->string('name', 150);
            // Liga o federación que organiza.
            $table->string('organizer', 150)->nullable();
            // Cierre de inscripción: hasta cuándo hay que tener a los jugadores habilitados.
            $table->date('registration_deadline')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['season_id', 'sport_id']);
        });

        Schema::create('category_competitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories');
            $table->foreignId('competition_id')->constrained('competitions')->cascadeOnDelete();

            $table->unique(['category_id', 'competition_id']);
        });

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

        Schema::table('matches', function (Blueprint $table) {
            $table->foreignId('competition_id')->nullable()->after('category_id')->constrained('competitions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('competition_id');
        });
        Schema::dropIfExists('competition_requirements');
        Schema::dropIfExists('category_competitions');
        Schema::dropIfExists('competitions');
    }
};
