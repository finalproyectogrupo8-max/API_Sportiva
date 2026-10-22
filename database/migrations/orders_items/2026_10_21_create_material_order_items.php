<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('delivered_quantity')->default(0);
            $table->string('notes', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_order_items');
    }
};
