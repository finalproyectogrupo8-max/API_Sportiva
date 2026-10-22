<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_coaches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories');
            $table->foreignId('coach_id')->constrained('coaches');
            $table->string('assignment_type', 40);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->index(['category_id', 'coach_id', 'assignment_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_coaches');
    }
};
