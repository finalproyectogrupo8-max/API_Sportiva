<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TIZ-10: el admin de plataforma es solo users.is_platform_admin, y lo que alguien puede hacer en un
 * club sale de los roles de la base del club. La membresía central solo da acceso.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('role'));
        Schema::table('tenant_memberships', fn (Blueprint $table) => $table->dropColumn('role'));
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('general')->after('email');
        });
        Schema::table('tenant_memberships', function (Blueprint $table) {
            $table->string('role')->default('admin')->after('tenant_id');
        });
    }
};
