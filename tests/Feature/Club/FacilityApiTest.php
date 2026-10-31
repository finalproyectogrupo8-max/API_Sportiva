<?php

namespace Tests\Feature\Club;

use App\Models\Club\Facility;
use App\Models\Club\FacilityComponent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** ABM de instalaciones (facilities): los casos de docs/guia-abm.md §11. */
class FacilityApiTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    private function token(string $role = 'admin'): string
    {
        return $this->clubToken($this->tenantA(), roles: [$role]);
    }

    public function test_lists_paginated_with_filters(): void
    {
        $this->inTenant($this->tenantA(), function () {
            Facility::factory()->count(22)->sequence(fn ($s) => [
                'name' => 'Cancha '.str_pad((string) $s->index, 2, '0', STR_PAD_LEFT),
                'type' => 'court',
                'status' => 'active',
            ])->create();
            Facility::factory()->create([
                'name' => 'Gimnasio Auxiliar',
                'type' => 'gym',
                'status' => 'out_of_service',
            ]);
        });
        $token = $this->token();

        $this->withClubToken($token)->getJson('/api/facilities')
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.total', 23)
            ->assertJsonStructure(['data', 'links', 'meta']);

        $this->withClubToken($token)->getJson('/api/facilities?type=gym')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Gimnasio Auxiliar');

        $this->withClubToken($token)->getJson('/api/facilities?status=out_of_service')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.status', 'out_of_service');

        $this->withClubToken($token)->getJson('/api/facilities?search=Cancha 0&sort=-name')
            ->assertOk()->assertJsonCount(10, 'data')->assertJsonPath('data.0.name', 'Cancha 09');
    }

    public function test_shows_one(): void
    {
        [$facility, $component] = $this->inTenant($this->tenantA(), function () {
            $fac = Facility::factory()->create([
                'name' => 'Cancha Principal',
                'type' => 'court',
                'status' => 'active',
            ]);
            $comp = FacilityComponent::factory()->create([
                'facility_id' => $fac->id,
                'name' => 'Aro Principal',
                'condition_status' => 'ok',
            ]);

            return [$fac, $comp];
        });

        $this->withClubToken($this->token())->getJson("/api/facilities/{$facility->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Cancha Principal')
            ->assertJsonPath('data.type', 'court')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonCount(1, 'data.components')
            ->assertJsonPath('data.components.0.name', 'Aro Principal');
    }

    public function test_creates_and_validates(): void
    {
        $token = $this->token('maintenance_manager');

        $this->withClubToken($token)->postJson('/api/facilities', [
            'name' => 'Vestuario Local',
            'type' => 'locker_room',
            'location' => 'Bajo platea oeste',
            'status' => 'active',
            'notes' => 'Remodelado en 2026',
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Vestuario Local')
            ->assertJsonPath('data.type', 'locker_room')
            ->assertJsonPath('data.status', 'active');

        $this->assertTrue($this->inTenant($this->tenantA(), fn () => Facility::where('name', 'Vestuario Local')->exists()));

        // Validación de campos requeridos y tipos inválidos
        $this->withClubToken($token)->postJson('/api/facilities', [
            'name' => '',
            'type' => 'tipo_invalido',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'type']);
    }

    public function test_updates_only_what_was_sent(): void
    {
        $facility = $this->inTenant($this->tenantA(), fn () => Facility::factory()->create([
            'name' => 'Cancha 2',
            'type' => 'court',
            'status' => 'active',
            'location' => 'Sector este',
        ]));

        $this->withClubToken($this->token('maintenance_manager'))->patchJson("/api/facilities/{$facility->id}", [
            'status' => 'out_of_service',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Cancha 2')
            ->assertJsonPath('data.status', 'out_of_service')
            ->assertJsonPath('data.location', 'Sector este');
    }

    public function test_delete_is_soft(): void
    {
        $facility = $this->inTenant($this->tenantA(), fn () => Facility::factory()->create());
        $token = $this->token('maintenance_manager');

        $this->withClubToken($token)->deleteJson("/api/facilities/{$facility->id}")->assertNoContent();
        $this->withClubToken($token)->getJson("/api/facilities/{$facility->id}")->assertNotFound();
        $this->assertNotNull($this->inTenant($this->tenantA(), fn () => Facility::withTrashed()->find($facility->id)?->deleted_at));
    }

    public function test_requires_token_and_permission(): void
    {
        $this->getJson('/api/facilities')->assertUnauthorized();

        // Profe puede ver instalaciones pero no crearlas ni eliminarlas
        $coachToken = $this->token('coach');
        $this->withClubToken($coachToken)->getJson('/api/facilities')->assertOk();
        $this->withClubToken($coachToken)->postJson('/api/facilities', [
            'name' => 'Cancha 3',
            'type' => 'court',
        ])->assertForbidden()->assertJsonPath('code', 'forbidden');

        $facility = $this->inTenant($this->tenantA(), fn () => Facility::factory()->create());
        $this->withClubToken($coachToken)->deleteJson("/api/facilities/{$facility->id}")
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden');
    }

    public function test_missing_returns_404(): void
    {
        $this->withClubToken($this->token())->getJson('/api/facilities/999999')->assertNotFound();
    }

    public function test_facility_of_another_club_is_not_visible(): void
    {
        $facilityB = $this->inTenant($this->tenantB(), fn () => Facility::factory()->create(['name' => 'Cancha Club B']));
        $token = $this->token();

        $this->withClubToken($token)->getJson("/api/facilities/{$facilityB->id}")->assertNotFound();
        $this->withClubToken($token)->patchJson("/api/facilities/{$facilityB->id}", ['name' => 'X'])->assertNotFound();
        $this->withClubToken($token)->deleteJson("/api/facilities/{$facilityB->id}")->assertNotFound();
        $this->withClubToken($token)->getJson('/api/facilities')->assertJsonCount(0, 'data');

        $this->assertSame('Cancha Club B', $this->inTenant($this->tenantB(), fn () => Facility::find($facilityB->id)->name));
    }
}
