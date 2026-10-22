<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // coaches se creó sin esta FK porque club_users todavía no existía.
        Schema::table('coaches', function (Blueprint $table) {
            $table->foreign('club_user_id')->references('id')->on('club_users');
        });
    }

    public function down(): void
    {
        Schema::table('coaches', function (Blueprint $table) {
            $table->dropForeign(['club_user_id']);
        });
    }
};
