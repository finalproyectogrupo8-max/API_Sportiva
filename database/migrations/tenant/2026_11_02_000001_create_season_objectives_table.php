<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Objetivos de la temporada (TIZ-20, módulo "Proyecto"): del club o de una categoría, con una meta
 * medible opcional y su avance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('season_objectives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained('seasons');
            $table->foreignId('category_id')->nullable()->constrained('categories');
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->decimal('target_value', 10, 2)->nullable();
            $table->decimal('current_value', 10, 2)->nullable();
            $table->string('unit', 30)->nullable();
            $table->string('status', 20)->default('in_progress');
            $table->date('due_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['season_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('season_objectives');
    }
};
