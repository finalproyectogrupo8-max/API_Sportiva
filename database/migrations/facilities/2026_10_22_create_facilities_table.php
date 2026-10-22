<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('type', 50); // gym | field | court | pool | track | other
            $table->string('location', 255)->nullable();
            $table->string('status', 20); // active | inactive | under_maintenance
            $table->text('notes')->nullable();
            $table->timestamps('created_at');
            $table->timestamp('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facilities');
    }
};
