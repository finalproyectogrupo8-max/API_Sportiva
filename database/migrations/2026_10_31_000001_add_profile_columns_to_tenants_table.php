<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TIZ-13: datos del perfil del club que hoy inventa el front. Viven en la base central porque se
 * muestran antes de entrar al club (selector de clubes y panel de plataforma).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('short_name', 6)->nullable()->after('name');
            $table->string('city', 120)->nullable()->after('short_name');
            $table->unsignedSmallInteger('founded_year')->nullable()->after('city');
            $table->char('primary_color', 7)->nullable()->after('founded_year');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['short_name', 'city', 'founded_year', 'primary_color']);
        });
    }
};
