<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_order_items', function (Blueprint $table) {
            $table->id();
            // CASCADE solo para borrar borradores: un pedido que salió de draft no se borra físicamente.
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->decimal('quantity', 10, 2);
            $table->decimal('delivered_quantity', 10, 2)->default(0);
            $table->string('notes', 255)->nullable();
        });

        DB::statement('ALTER TABLE material_order_items ADD CONSTRAINT material_order_items_quantity_check CHECK (quantity > 0)');
        DB::statement('ALTER TABLE material_order_items ADD CONSTRAINT material_order_items_delivered_check CHECK (delivered_quantity <= quantity)');
    }

    public function down(): void
    {
        Schema::dropIfExists('material_order_items');
    }
};
