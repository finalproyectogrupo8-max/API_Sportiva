<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TIZ-32: estado de cada requisito de habilitación por jugador. Sin fila, el requisito está
 * pendiente. Un requisito aprobado que pasó su vencimiento cuenta como vencido.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('competition_requirement_id')->constrained('competition_requirements')->cascadeOnDelete();
            // pending / submitted / approved / rejected / expired
            $table->string('status', 20);
            $table->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('club_users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('rejection_reason', 255)->nullable();
            $table->timestamps();

            $table->unique(['player_id', 'competition_requirement_id']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_requirements');
    }
};
