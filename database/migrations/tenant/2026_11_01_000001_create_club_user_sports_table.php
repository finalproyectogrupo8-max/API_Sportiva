<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_user_sports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_user_id')->constrained('club_users')->cascadeOnDelete();
            $table->foreignId('sport_id')->constrained('sports')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['club_user_id', 'sport_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_user_sports');
    }
};
