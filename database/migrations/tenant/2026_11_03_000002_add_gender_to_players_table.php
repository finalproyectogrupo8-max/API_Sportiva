<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** TIZ-40: género del jugador (female / male / other). Los existentes quedan sin dato. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->string('gender', 10)->nullable()->after('birth_date');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('gender');
        });
    }
};
