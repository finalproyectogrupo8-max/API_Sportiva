<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TIZ-33: convocados a un partido. Cada uno tiene un link personal (token) para confirmar si va,
 * sin cuenta ni login.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_callups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players');
            $table->unsignedSmallInteger('shirt_number')->nullable();
            // pending / going / not_going
            $table->string('response', 20)->default('pending');
            $table->string('response_note', 255)->nullable();
            $table->timestamp('responded_at')->nullable();
            // "<club>.<aleatorio>": el club dice en qué base buscar.
            $table->string('token', 100)->unique();
            $table->timestamps();

            $table->unique(['match_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_callups');
    }
};
