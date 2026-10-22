<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 100)->unique();
            $table->string('database_name', 100)->unique();
            $table->string('database_host', 255);
            $table->unsignedSmallInteger('database_port')->default(3306);
            $table->string('database_username_ref', 255)->nullable();
            $table->string('database_secret_ref', 255);
            $table->string('status', 20); // provisioning | active | suspended
            $table->timestamps('created_at');
            $table->timestamp('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
