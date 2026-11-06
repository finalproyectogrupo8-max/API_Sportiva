<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Temporadas por deporte: cada coordinador arma las de su deporte. Sin deporte (null) la temporada
 * es de todo el club y la puede usar cualquier deporte, como hasta ahora.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seasons', function (Blueprint $table) {
            $table->foreignId('sport_id')->nullable()->after('id')->constrained('sports');
        });
    }

    public function down(): void
    {
        Schema::table('seasons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sport_id');
        });
    }
};
