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
            $table->foreignId('player_id')->constrained('players')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->smallInteger('shirt_number')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps(); // created_at, updated_at


            $table->unique(['player_id', 'category_id', 'start_date'], 'unique_player_category_membership');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_category_memberships');
    }
};
