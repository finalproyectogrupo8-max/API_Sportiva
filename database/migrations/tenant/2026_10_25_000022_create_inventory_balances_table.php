<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials');
            $table->foreignId('location_id')->constrained('storage_locations');
            $table->decimal('quantity', 12, 2)->default(0);
            $table->timestamp('updated_at')->nullable();

            $table->unique(['material_id', 'location_id']);
        });

        DB::statement('ALTER TABLE inventory_balances ADD CONSTRAINT inventory_balances_quantity_check CHECK (quantity >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_balances');
    }
};
