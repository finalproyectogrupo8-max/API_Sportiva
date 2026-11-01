<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** TIZ-33: publicación de la convocatoria y aviso de cambios después de publicar. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->timestamp('callup_published_at')->nullable()->after('notes');
            // Link de la página pública: "<club>.<aleatorio>".
            $table->string('public_token', 100)->nullable()->unique()->after('callup_published_at');
            // Cambió la fecha, la hora o el lugar después de publicar: hay que volver a avisar.
            $table->boolean('updated_after_publish')->default(false)->after('public_token');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropUnique(['public_token']);
            $table->dropColumn(['callup_published_at', 'public_token', 'updated_after_publish']);
        });
    }
};
