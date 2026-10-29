<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SupportTicketSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('tickets');
    }

    public function test_guest_can_submit_valid_ticket(): void
    {
        $payload = [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'club_name' => 'Club Deportivo Central',
            'subject' => 'Consulta sobre afiliación',
            'message' => 'Hola, quisiera conocer más sobre el sistema de cuotas y carnets.',
        ];

        $response = $this->postJson('/api/tickets', $payload);

        $response->assertCreated()
            ->assertJsonStructure(['data' => ['id']]);

        $ticketId = $response->json('data.id');
        $this->assertIsInt($ticketId);
        $this->assertGreaterThan(0, $ticketId);

        $this->assertDatabaseHas('support_tickets', [
            'id' => $ticketId,
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'club_name' => 'Club Deportivo Central',
            'subject' => 'Consulta sobre afiliación',
            'status' => TicketStatus::New->value,
            'handled_by' => null,
            'resolved_at' => null,
            'admin_notes' => null,
        ]);
    }

    public function test_guest_can_submit_ticket_without_optional_club_name(): void
    {
        $payload = [
            'name' => 'María Gomez',
            'email' => 'maria@example.com',
            'subject' => 'Duda general',
            'message' => 'Mensaje de prueba con más de diez caracteres.',
        ];

        $response = $this->postJson('/api/tickets', $payload);

        $response->assertCreated()
            ->assertJsonStructure(['data' => ['id']]);

        $this->assertDatabaseHas('support_tickets', [
            'email' => 'maria@example.com',
            'club_name' => null,
        ]);
    }

    public function test_validation_rejects_missing_or_invalid_fields(): void
    {
        // 1. Campos obligatorios faltantes
        $this->postJson('/api/tickets', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'subject', 'message']);

        // 2. Email inválido
        $this->postJson('/api/tickets', [
            'name' => 'Test',
            'email' => 'no-es-un-email',
            'subject' => 'Asunto',
            'message' => 'Mensaje válido de longitud adecuada.',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        // 3. Mensaje demasiado corto (< 10 caracteres)
        $this->postJson('/api/tickets', [
            'name' => 'Test',
            'email' => 'test@example.com',
            'subject' => 'Asunto',
            'message' => 'Corto',
        ])->assertUnprocessable()->assertJsonValidationErrors('message');

        // 4. Exceso de caracteres
        $this->postJson('/api/tickets', [
            'name' => str_repeat('a', 121),
            'email' => str_repeat('a', 180).'@example.com',
            'club_name' => str_repeat('c', 151),
            'subject' => str_repeat('s', 151),
            'message' => str_repeat('m', 3001),
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'name',
            'email',
            'club_name',
            'subject',
            'message',
        ]);
    }

    public function test_validation_prohibits_server_and_immutable_fields(): void
    {
        $this->postJson('/api/tickets', [
            'name' => 'Hacker',
            'email' => 'hacker@example.com',
            'subject' => 'Intento de escalada',
            'message' => 'Intentando forzar estado a resuelto.',
            'status' => 'resolved',
            'handled_by' => 1,
            'admin_notes' => 'Nota inyectada',
            'resolved_at' => now()->toIso8601String(),
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'status',
            'handled_by',
            'admin_notes',
            'resolved_at',
        ]);

        $this->assertDatabaseCount('support_tickets', 0);
    }

    public function test_honeypot_field_returns_201_without_persisting_ticket(): void
    {
        $payload = [
            'name' => 'Spam Bot',
            'email' => 'bot@spammer.test',
            'subject' => 'Compre crypto ya',
            'message' => 'Mensaje spam con contenido promocional no deseado.',
            'website' => 'https://spam-sitio.test/oferta',
        ];

        $response = $this->postJson('/api/tickets', $payload);

        $response->assertCreated()
            ->assertExactJson([
                'data' => [
                    'id' => 0,
                ],
            ]);

        $this->assertDatabaseCount('support_tickets', 0);
    }

    public function test_honeypot_empty_or_null_is_allowed_and_persisted(): void
    {
        $payload = [
            'name' => 'Usuario Real',
            'email' => 'real@example.com',
            'subject' => 'Consulta limpia',
            'message' => 'Mensaje válido con campo honeypot vacío.',
            'website' => '',
        ];

        $response = $this->postJson('/api/tickets', $payload);

        $response->assertCreated();
        $this->assertDatabaseCount('support_tickets', 1);
    }

    public function test_rate_limiting_throttles_after_limit(): void
    {
        $payload = [
            'name' => 'Usuario Frecuente',
            'email' => 'frecuente@example.com',
            'subject' => 'Consulta rápida',
            'message' => 'Primer mensaje con longitud suficiente.',
        ];

        // Primeras 5 peticiones exitosas
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/tickets', $payload)->assertCreated();
        }

        // La 6ta petición debe responder 429 Too Many Requests
        $this->postJson('/api/tickets', $payload)->assertStatus(429);
    }

    public function test_rate_limit_is_per_email_so_one_visitor_does_not_block_others(): void
    {
        // Todos los envíos de la landing llegan con la IP del servidor de Next.
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/tickets', [
                'name' => 'Visitante Uno',
                'email' => 'uno@example.com',
                'subject' => 'Solicitud de demo',
                'message' => 'Quiero solicitar una demo de Sportiva.',
            ])->assertCreated();
        }

        $this->postJson('/api/tickets', [
            'name' => 'Visitante Dos',
            'email' => 'dos@example.com',
            'subject' => 'Solicitud de demo',
            'message' => 'Quiero solicitar una demo de Sportiva.',
        ])->assertCreated();
    }

    public function test_legacy_contact_route_was_removed(): void
    {
        $this->postJson('/api/contacto', ['name' => 'X', 'email' => 'x@example.com'])->assertNotFound();
    }
}
