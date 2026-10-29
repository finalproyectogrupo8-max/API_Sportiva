<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * El formulario de /contacto ahora crea tickets (los ve el admin en /admin/tickets).
 * Pasa las consultas que quedaron en `contacts` a `support_tickets` y borra la tabla.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contacts')) {
            return;
        }

        DB::table('contacts')->orderBy('id')->each(function (object $contact): void {
            $message = trim((string) $contact->message);

            DB::table('support_tickets')->insert([
                'name' => Str::limit($contact->name, 120, ''),
                'email' => Str::limit($contact->email, 190, ''),
                'subject' => 'Consulta desde la página de contacto',
                'message' => $message !== '' ? $message : 'Consulta enviada sin mensaje.',
                'status' => 'new',
                'created_at' => $contact->created_at,
                'updated_at' => $contact->updated_at,
            ]);
        });

        Schema::drop('contacts');
    }

    public function down(): void
    {
        // Vuelve a crear la tabla vacía: los tickets migrados quedan en support_tickets.
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }
};
