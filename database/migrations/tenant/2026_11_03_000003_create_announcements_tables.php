<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TIZ-24: comunicados por mail del club. Los destinatarios se congelan al enviar (uno por dirección),
 * así el historial dice a quién se le mandó aunque después cambien los datos del club.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('subject', 150);
            $table->text('body');
            $table->json('audience');
            // draft / sending / sent
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->constrained('club_users');
            $table->foreignId('sent_by')->nullable()->constrained('club_users');
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('recipients_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
        });

        Schema::create('announcement_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('announcements')->cascadeOnDelete();
            // Normalizado (minúsculas, sin espacios): la deduplicación es el UNIQUE.
            $table->string('email', 255);
            $table->string('name', 150);
            // De dónde salió: [{type: player_contact|coach|club_user, id}].
            $table->json('sources');
            // pending / sent / failed
            $table->string('status', 20)->default('pending');
            $table->string('error', 255)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['announcement_id', 'email']);
            $table->index(['announcement_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_recipients');
        Schema::dropIfExists('announcements');
    }
};
