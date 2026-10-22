<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->onDelete('cascade');
            $table->foreignId('training_id')->nullable()->constrained('trainings')->onDelete('cascade');
            $table->foreignId('match_id')->nullable()->constrained('matches')->onDelete('cascade');
            $table->string('status', 20);
            $table->string('notes', 255)->nullable();
            $table->foreignId('recorded_by')->constrained('users')->onDelete('cascade');
            $table->timestamps('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_attendance');
    }
};
