<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** TIZ-37: cuándo salió el último aviso por mail al contacto principal del convocado. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('match_callups', function (Blueprint $table) {
            $table->timestamp('notified_at')->nullable()->after('responded_at');
        });
    }

    public function down(): void
    {
        Schema::table('match_callups', function (Blueprint $table) {
            $table->dropColumn('notified_at');
        });
    }
};
