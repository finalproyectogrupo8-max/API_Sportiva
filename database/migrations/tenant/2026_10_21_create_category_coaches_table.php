<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_coaches', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->foreignId('coach_id')->constrained('coaches')->onDelete('cascade');
            $table->string('assignment_type', 40);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_coaches');
    }
};
