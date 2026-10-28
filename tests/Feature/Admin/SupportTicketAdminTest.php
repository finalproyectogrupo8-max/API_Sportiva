<?php

namespace Tests\Feature\Admin;

use App\Enums\TicketStatus;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupportTicketAdminTest extends TestCase
{
    use RefreshDatabase;

    private function asAdmin(): User
    {
        $admin = User::factory()->platformAdmin()->create();
        Sanctum::actingAs($admin, ['platform:admin']);

        return $admin;
    }

    public function test_requires_authentication(): void
    {
        $ticket = SupportTicket::factory()->create();

        $this->getJson('/api/admin/tickets')->assertUnauthorized();
        $this->getJson("/api/admin/tickets/{$ticket->id}")->assertUnauthorized();
        $this->patchJson("/api/admin/tickets/{$ticket->id}", ['status' => 'in_review'])->assertUnauthorized();
    }

    public function test_forbids_non_platform_admin(): void
    {
        $user = User::factory()->create(['is_platform_admin' => false]);
        Sanctum::actingAs($user, ['platform:admin']);
        $ticket = SupportTicket::factory()->create();

        $this->getJson('/api/admin/tickets')->assertForbidden()->assertJsonPath('code', 'forbidden');
        $this->getJson("/api/admin/tickets/{$ticket->id}")->assertForbidden()->assertJsonPath('code', 'forbidden');
        $this->patchJson("/api/admin/tickets/{$ticket->id}", ['status' => 'in_review'])
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden');
    }

    public function test_forbids_token_without_platform_admin_ability(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        Sanctum::actingAs($admin, ['tenant:select']); // Sin ability platform:admin
        $ticket = SupportTicket::factory()->create();

        $this->getJson('/api/admin/tickets')->assertForbidden()->assertJsonPath('code', 'forbidden');
        $this->getJson("/api/admin/tickets/{$ticket->id}")->assertForbidden()->assertJsonPath('code', 'forbidden');
        $this->patchJson("/api/admin/tickets/{$ticket->id}", ['status' => 'in_review'])
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden');
    }

    public function test_admin_can_list_tickets_paginated_and_ordered_by_default(): void
    {
        $this->asAdmin();

        $oldTicket = SupportTicket::factory()->create(['created_at' => now()->subDays(2)]);
        $recentTicket = SupportTicket::factory()->create(['created_at' => now()->subDay()]);
        $newestTicket = SupportTicket::factory()->create(['created_at' => now()]);

        $response = $this->getJson('/api/admin/tickets');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'name',
                        'email',
                        'club_name',
                        'subject',
                        'message',
                        'status',
                        'admin_notes',
                        'handled_by',
                        'resolved_at',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonCount(3, 'data');

        // Orden desc por defecto: primero el más nuevo
        $this->assertSame($newestTicket->id, $response->json('data.0.id'));
        $this->assertSame($recentTicket->id, $response->json('data.1.id'));
        $this->assertSame($oldTicket->id, $response->json('data.2.id'));
    }

    public function test_admin_can_filter_tickets_by_status(): void
    {
        $this->asAdmin();

        SupportTicket::factory()->create(['status' => TicketStatus::New]);
        SupportTicket::factory()->inReview()->count(2)->create();
        SupportTicket::factory()->resolved()->count(3)->create();

        $this->getJson('/api/admin/tickets?status=new')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/admin/tickets?status=in_review')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson('/api/admin/tickets?status=resolved')
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $this->getJson('/api/admin/tickets?status=inexistente')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_admin_can_view_ticket_details(): void
    {
        $admin = $this->asAdmin();

        $ticket = SupportTicket::factory()->resolved($admin)->create([
            'name' => 'Carlos Rodriguez',
            'email' => 'carlos@example.com',
            'club_name' => 'Club San Martín',
            'subject' => 'Consulta de tarifas',
            'message' => 'Necesitamos conocer la lista de precios vigente.',
            'admin_notes' => 'Respondido telefónicamente.',
        ]);

        $response = $this->getJson("/api/admin/tickets/{$ticket->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $ticket->id)
            ->assertJsonPath('data.name', 'Carlos Rodriguez')
            ->assertJsonPath('data.email', 'carlos@example.com')
            ->assertJsonPath('data.club_name', 'Club San Martín')
            ->assertJsonPath('data.subject', 'Consulta de tarifas')
            ->assertJsonPath('data.message', 'Necesitamos conocer la lista de precios vigente.')
            ->assertJsonPath('data.status', 'resolved')
            ->assertJsonPath('data.admin_notes', 'Respondido telefónicamente.')
            ->assertJsonPath('data.handled_by', $admin->id);

        $this->assertNotNull($response->json('data.resolved_at'));
        $this->assertNotNull($response->json('data.created_at'));
    }

    public function test_show_returns_404_when_ticket_does_not_exist(): void
    {
        $this->asAdmin();

        $this->getJson('/api/admin/tickets/999999')->assertNotFound();
    }

    public function test_admin_can_update_admin_notes_without_changing_status(): void
    {
        $this->asAdmin();
        $ticket = SupportTicket::factory()->create(['status' => TicketStatus::New]);

        $response = $this->patchJson("/api/admin/tickets/{$ticket->id}", [
            'admin_notes' => 'Se verificaron los datos del club.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'new')
            ->assertJsonPath('data.admin_notes', 'Se verificaron los datos del club.');

        $this->assertSame('Se verificaron los datos del club.', $ticket->fresh()->admin_notes);
        $this->assertNull($ticket->fresh()->resolved_at);
        $this->assertNull($ticket->fresh()->handled_by);
    }

    public function test_admin_can_change_status_to_in_review(): void
    {
        $this->asAdmin();
        $ticket = SupportTicket::factory()->create(['status' => TicketStatus::New]);

        $response = $this->patchJson("/api/admin/tickets/{$ticket->id}", [
            'status' => 'in_review',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'in_review');

        $this->assertSame(TicketStatus::InReview, $ticket->fresh()->status);
        $this->assertNull($ticket->fresh()->resolved_at);
    }

    public function test_changing_status_to_resolved_sets_resolved_at_and_handled_by(): void
    {
        $admin = $this->asAdmin();
        $ticket = SupportTicket::factory()->create(['status' => TicketStatus::New]);

        $response = $this->patchJson("/api/admin/tickets/{$ticket->id}", [
            'status' => 'resolved',
            'admin_notes' => 'Se resolvió la duda por email.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'resolved')
            ->assertJsonPath('data.handled_by', $admin->id)
            ->assertJsonPath('data.admin_notes', 'Se resolvió la duda por email.');

        $fresh = $ticket->fresh();
        $this->assertSame(TicketStatus::Resolved, $fresh->status);
        $this->assertSame($admin->id, $fresh->handled_by);
        $this->assertNotNull($fresh->resolved_at);
    }

    public function test_changing_status_from_resolved_to_another_clears_resolved_at(): void
    {
        $admin = $this->asAdmin();
        $ticket = SupportTicket::factory()->resolved($admin)->create();

        $this->assertNotNull($ticket->resolved_at);

        // Volver a in_review
        $response = $this->patchJson("/api/admin/tickets/{$ticket->id}", [
            'status' => 'in_review',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'in_review')
            ->assertJsonPath('data.resolved_at', null);

        $this->assertNull($ticket->fresh()->resolved_at);
    }

    public function test_update_prohibits_immutable_fields(): void
    {
        $this->asAdmin();
        $ticket = SupportTicket::factory()->create([
            'name' => 'Original',
            'email' => 'original@example.com',
            'subject' => 'Original',
        ]);

        $this->patchJson("/api/admin/tickets/{$ticket->id}", [
            'name' => 'Modificado',
            'email' => 'cambio@example.com',
            'subject' => 'Nuevo asunto',
            'message' => 'Nuevo mensaje',
            'website' => 'spam',
            'handled_by' => 99,
            'resolved_at' => now()->toIso8601String(),
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'name',
            'email',
            'subject',
            'message',
            'website',
            'handled_by',
            'resolved_at',
        ]);

        $this->assertSame('Original', $ticket->fresh()->name);
    }
}
