<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TIZ-15: tutores y contactos del jugador. Son a quienes llegan la convocatoria por mail y los
 * links de WhatsApp (TIZ-33). Un jugador puede tener varios; uno es el principal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->string('name', 150);
            // mother / father / guardian / self / other
            $table->string('relationship', 20);
            $table->string('phone', 40)->nullable();
            $table->string('email', 255)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['player_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_contacts');
    }
};
