<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('type', 60)->default('general')->after('club_user_id');
            $table->json('data')->nullable()->after('body');
            $table->timestamp('updated_at')->nullable()->after('created_at');

            $table->index(['club_user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['club_user_id', 'type']);
            $table->dropColumn(['type', 'data', 'updated_at']);
        });
    }
};
