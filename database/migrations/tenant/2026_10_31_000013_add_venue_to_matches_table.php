<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Sede del partido de local (TIZ-39). `venue` y `maps_url` quedan como texto: también sirven de visitante. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->foreignId('venue_id')->nullable()->after('meet_at')->constrained('venues');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('venue_id');
        });
    }
};
