<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apparel_order_items', function (Blueprint $table) {
            $table->id();
            // CASCADE solo para borrar borradores: un pedido que salió de draft no se borra físicamente.
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players');
            $table->foreignId('apparel_item_id')->constrained('apparel_items');
            $table->string('size', 20);
            $table->smallInteger('shirt_number')->nullable();
            $table->string('alias', 50)->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('delivered_quantity')->default(0);
            $table->string('notes', 255)->nullable();
        });

        DB::statement('ALTER TABLE apparel_order_items ADD CONSTRAINT apparel_order_items_quantity_check CHECK (quantity > 0)');
        DB::statement('ALTER TABLE apparel_order_items ADD CONSTRAINT apparel_order_items_delivered_check CHECK (delivered_quantity <= quantity)');
    }

    public function down(): void
    {
        Schema::dropIfExists('apparel_order_items');
    }
};
