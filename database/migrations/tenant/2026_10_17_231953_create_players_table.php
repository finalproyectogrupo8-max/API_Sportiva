<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->date('birth_date')->nullable();
            $table->string('document_number', 30)->nullable();
            $table->string('registration_number', 50)->nullable();
            $table->string('preferred_position', 50)->nullable();
            $table->string('dominant_side', 20)->nullable();
            $table->string('status', 20)->default('active'); // active | inactive | transferred
            $table->text('notes')->nullable();
            $table->timestamps(); // created_at, updated_at
            $table->softDeletes();

            $table->index(['last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
