<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_records', function (Blueprint $table) {
            $table->id();
            $table->string('alert_type', 60);
            $table->string('subject_type', 60);
            $table->unsignedBigInteger('subject_id');
            $table->string('dedup_key', 191)->unique();
            $table->timestamp('notified_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['alert_type', 'subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_records');
    }
};
