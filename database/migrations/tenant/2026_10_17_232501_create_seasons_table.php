<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20);
            $table->timestamps();
        });

        // El Blueprint no tiene un método para CHECK: va en SQL.
        DB::statement('ALTER TABLE seasons ADD CONSTRAINT seasons_dates_check CHECK (end_date >= start_date)');
    }

    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};
