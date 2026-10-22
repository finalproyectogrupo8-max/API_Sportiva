<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories');
            $table->foreignId('coach_id')->nullable()->constrained('coaches');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('location', 150)->nullable();
            $table->text('objectives')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'starts_at']);
        });

        DB::statement('ALTER TABLE trainings ADD CONSTRAINT trainings_dates_check CHECK (ends_at > starts_at)');
    }

    public function down(): void
    {
        Schema::dropIfExists('trainings');
    }
};
