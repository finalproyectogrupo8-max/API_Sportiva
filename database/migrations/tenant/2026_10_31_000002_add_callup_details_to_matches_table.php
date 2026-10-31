<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TIZ-16: lo que necesita el aviso de la convocatoria (TIZ-33). competition_id llega con TIZ-31
 * y los campos de publicación (callup_published_at, public_token) con TIZ-33.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dateTime('meet_at')->nullable()->after('match_date');
            $table->string('maps_url', 500)->nullable()->after('venue');
            // home / away: camiseta titular o alternativa. uniform_color sigue siendo el color.
            $table->string('uniform', 10)->nullable()->after('is_home');
            $table->text('what_to_bring')->nullable()->after('uniform_color');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn(['meet_at', 'maps_url', 'uniform', 'what_to_bring']);
        });
    }
};
