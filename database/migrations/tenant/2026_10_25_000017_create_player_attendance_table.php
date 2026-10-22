<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players');
            $table->foreignId('training_id')->nullable()->constrained('trainings');
            $table->foreignId('match_id')->nullable()->constrained('matches');
            $table->string('status', 20);
            $table->string('notes', 255)->nullable();
            $table->foreignId('recorded_by')->constrained('club_users');
            $table->timestamp('created_at')->useCurrent();
            // No está en el borrador: registra cuándo se corrigió la asistencia.
            $table->timestamp('updated_at')->nullable();

            // Dos índices y no uno sobre las tres columnas: MySQL no compara NULL en un UNIQUE.
            $table->unique(['player_id', 'training_id']);
            $table->unique(['player_id', 'match_id']);
        });

        DB::statement('ALTER TABLE player_attendance ADD CONSTRAINT player_attendance_event_check CHECK ((training_id IS NULL) <> (match_id IS NULL))');
    }

    public function down(): void
    {
        Schema::dropIfExists('player_attendance');
    }
};
