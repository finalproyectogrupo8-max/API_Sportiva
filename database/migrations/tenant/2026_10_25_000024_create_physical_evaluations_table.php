<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('physical_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players');
            $table->foreignId('category_id')->nullable()->constrained('categories');
            $table->foreignId('evaluator_coach_id')->constrained('coaches');
            $table->dateTime('evaluated_at');
            $table->text('summary')->nullable();
            $table->text('recommendations')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['player_id', 'evaluated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('physical_evaluations');
    }
};
