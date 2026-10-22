<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('description', 255)->nullable();
            $table->string('unit', 30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // UNIQUE de name que ignora los borrados: la expresión da NULL en las filas borradas,
        // y MySQL permite varios NULL en un índice único. El Blueprint no lo soporta: va en SQL.
        DB::statement('CREATE UNIQUE INDEX materials_name_unique ON materials ((CASE WHEN deleted_at IS NULL THEN name END))');
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
