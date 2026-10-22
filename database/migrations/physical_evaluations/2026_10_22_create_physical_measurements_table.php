<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('physical_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained('physical_evaluations')->cascadeOnDelete();
            $table->string('metric_key', 80); // height_cm | weight_kg | sprint_20m_sec
            $table->string('metric_name', 120);
            $table->decimal('number_value', 10, 3)->nullable();
            $table->string('unit', 30)->nullable(); // cm | kg | sec | reps...
            $table->string('text_value', 255)->nullable();
            $table->timestamps('created_at');
            $table->timestamp('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('physical_measurements');
    }
};
