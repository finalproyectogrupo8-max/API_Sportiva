<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained('facilities');
            $table->foreignId('component_id')->nullable()->constrained('facility_components');
            $table->foreignId('reported_by')->constrained('club_users');
            $table->foreignId('assigned_to')->nullable()->constrained('club_users');
            $table->string('title', 150);
            $table->text('description');
            $table->string('priority', 20)->default('normal');
            $table->string('status', 30);
            $table->dateTime('reported_at');
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_requests');
    }
};
