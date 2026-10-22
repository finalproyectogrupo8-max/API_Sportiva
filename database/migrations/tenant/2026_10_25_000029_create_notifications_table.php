<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla propia, no la del canal "database" de Laravel (que usa UUID y notifiable polimórfico).
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_user_id')->constrained('club_users');
            $table->string('title', 150);
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['club_user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
