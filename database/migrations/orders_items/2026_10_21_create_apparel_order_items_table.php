<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apparel_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order')->constrained('orders')->onDelete('cascade');
            $table->foreignId('player_id')->constrained('players')->onDelete('cascade');
            $table->foreignId('apparel_item_id')->constrained('apparel_items')->onDelete('cascade');
            $table->string('size', 20);
            $table->smallInteger('shirt_number')->nullable();
            $table->string('alias', 50)->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('delivered_quantity')->default(0);
            $table->string('notes', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apparel_order_items');
    }
};
