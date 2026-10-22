<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Libro inmutable: sin updated_at. La regla de signo de "adjustment" (D14) se decide en el
        // change del módulo, así que todavía no lleva CHECK sobre quantity.
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials');
            $table->foreignId('location_id')->constrained('storage_locations');
            $table->string('movement_type', 20);
            $table->decimal('quantity', 12, 2);
            $table->char('transfer_group_id', 36)->nullable()->index();
            $table->foreignId('order_id')->nullable()->constrained('orders');
            $table->foreignId('performed_by')->constrained('club_users');
            $table->string('reason', 255)->nullable();
            $table->string('reference', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['material_id', 'location_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
