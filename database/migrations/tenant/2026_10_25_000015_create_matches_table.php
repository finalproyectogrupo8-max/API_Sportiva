<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories');
            $table->string('opponent', 120);
            $table->dateTime('match_date');
            $table->string('venue', 200)->nullable();
            $table->boolean('is_home');
            $table->string('uniform_color', 50)->nullable();
            $table->unsignedSmallInteger('our_score')->nullable();
            $table->unsignedSmallInteger('opponent_score')->nullable();
            $table->string('status', 20);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'match_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
