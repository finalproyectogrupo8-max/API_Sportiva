<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coaches', function (Blueprint $table) {
            $table->id();
            // Sin FK hasta que exista club_users: la agrega una migración de club-access-control.
            $table->foreignId('club_user_id')->nullable()->constrained('club_users')->cascadeOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('phone', 40)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('status', 20);
            $table->timestamps('created_at');
            $table->timestamps('updated_at');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coaches');
    }
};
