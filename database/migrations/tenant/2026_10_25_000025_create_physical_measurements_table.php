<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('physical_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained('physical_evaluations')->cascadeOnDelete();
            $table->string('metric_key', 80)->index();
            $table->string('metric_name', 120);
            $table->decimal('numeric_value', 10, 3)->nullable();
            $table->string('unit', 30)->nullable();
            $table->string('text_value', 255)->nullable();
            $table->string('notes', 255)->nullable();

            $table->unique(['evaluation_id', 'metric_key']);
        });

        DB::statement('ALTER TABLE physical_measurements ADD CONSTRAINT physical_measurements_value_check CHECK (numeric_value IS NOT NULL OR text_value IS NOT NULL)');
    }

    public function down(): void
    {
        Schema::dropIfExists('physical_measurements');
    }
};
