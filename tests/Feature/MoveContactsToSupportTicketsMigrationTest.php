<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MoveContactsToSupportTicketsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_moves_existing_contacts_to_support_tickets_and_drops_the_table(): void
    {
        // La migración ya corrió con RefreshDatabase: se recrea `contacts` con datos viejos.
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->text('message')->nullable();
            $table->timestamps();
        });
        DB::table('contacts')->insert([
            ['name' => 'Ana', 'email' => 'ana@club.com', 'message' => 'Queremos probar Sportiva.', 'created_at' => '2026-10-28 10:00:00', 'updated_at' => '2026-10-28 10:00:00'],
            ['name' => 'Beto', 'email' => 'beto@club.com', 'message' => null, 'created_at' => '2026-10-28 11:00:00', 'updated_at' => '2026-10-28 11:00:00'],
        ]);

        $migration = require database_path('migrations/2026_10_29_170000_move_contacts_to_support_tickets.php');
        $migration->up();

        $this->assertFalse(Schema::hasTable('contacts'));
        $this->assertDatabaseHas('support_tickets', [
            'email' => 'ana@club.com',
            'message' => 'Queremos probar Sportiva.',
            'subject' => 'Consulta desde la página de contacto',
            'status' => 'new',
            'created_at' => '2026-10-28 10:00:00',
        ]);
        $this->assertDatabaseHas('support_tickets', [
            'email' => 'beto@club.com',
            'message' => 'Consulta enviada sin mensaje.',
        ]);
    }
}
