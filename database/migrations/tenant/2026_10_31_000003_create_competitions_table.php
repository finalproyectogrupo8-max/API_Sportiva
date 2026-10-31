<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** TIZ-31: ligas y torneos del club, por deporte y temporada. */
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
    }

    public function down(): void
    {
        Schema::dropIfExists('competitions');
    }
};
