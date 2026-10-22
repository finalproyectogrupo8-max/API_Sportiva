<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_user_roles', function (Blueprint $table) {
            $table->foreignId('club_user_id')->constrained('club_users');
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->primary(['club_user_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_user_roles');
    }
};
