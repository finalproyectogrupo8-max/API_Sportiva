<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('global_user_id')->unique();
            $table->string('display_name', 120);
            $table->string('status', 20)->default('active');
            $table->timestamps('created_at');
            $table->timestamps('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_users');
    }
};
