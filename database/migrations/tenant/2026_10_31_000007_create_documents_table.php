<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TIZ-29: archivos del club (foto del jugador, apto médico, ficha...). Una sola tabla que se cuelga
 * de cualquier entidad. El archivo vive en un disco privado; nunca tiene URL pública.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('documentable_type', 50);
            $table->unsignedBigInteger('documentable_id');
            // photo / medical / id_document / federation_card / authorization / other
            $table->string('kind', 30);
            $table->string('original_name', 255);
            $table->string('disk', 30);
            $table->string('path', 500);
            $table->string('mime', 100);
            $table->unsignedInteger('size');
            $table->foreignId('uploaded_by')->nullable()->constrained('club_users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['documentable_type', 'documentable_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
