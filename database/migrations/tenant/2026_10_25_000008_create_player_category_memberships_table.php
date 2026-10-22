<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_category_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players');
            $table->foreignId('category_id')->constrained('categories');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->smallInteger('shirt_number')->nullable();
            $table->string('status', 20);
            $table->timestamps();

            $table->index(['player_id', 'category_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_category_memberships');
    }
};
