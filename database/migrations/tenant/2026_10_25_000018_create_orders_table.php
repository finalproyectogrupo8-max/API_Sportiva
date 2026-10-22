<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 40)->unique();
            $table->string('type', 20);
            $table->foreignId('requested_by')->constrained('club_users');
            $table->foreignId('category_id')->nullable()->constrained('categories');
            $table->string('status', 30);
            $table->string('priority', 20)->default('normal');
            $table->text('description')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('club_users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
