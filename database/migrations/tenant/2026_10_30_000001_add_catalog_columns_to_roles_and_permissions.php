<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Roles estándar y custom, grilla por módulo y alcance "sus categorías" (docs/roles-y-permisos.md §8).
 * Las tablas están vacías hasta esta versión: nadie las cargaba antes de club:sync-permissions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('slug', 80)->unique()->after('id');
            $table->boolean('is_system')->default(false)->after('description');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->string('module', 40)->after('name');
        });

        Schema::table('role_permissions', function (Blueprint $table) {
            $table->string('scope', 10)->default('all')->after('permission_id');
        });
    }

    public function down(): void
    {
        Schema::table('role_permissions', fn (Blueprint $table) => $table->dropColumn('scope'));
        Schema::table('permissions', fn (Blueprint $table) => $table->dropColumn('module'));
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'is_system']);
        });
    }
};
