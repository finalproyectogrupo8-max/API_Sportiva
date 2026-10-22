<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_request_id')->nullable()->constrained('repair_requests');
            $table->foreignId('facility_id')->constrained('facilities');
            $table->foreignId('performed_by')->nullable()->constrained('club_users');
            $table->dateTime('performed_at');
            $table->text('description');
            $table->decimal('cost', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_records');
    }
};
