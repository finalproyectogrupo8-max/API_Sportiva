<?php

namespace Tests\Feature\Club;

use App\Models\Club\Facility;
use App\Models\Club\FacilityComponent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** ABM de componentes de instalaciones (facilities.components): rutas shallow y borrado real. */
class FacilityComponentApiTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    private function token(string $role = 'admin'): string
    {
        return $this->clubToken($this->tenantA(), roles: [$role]);
    }

    public function test_lists_components_for_facility_paginated_with_filters(): void
    {
        [$facility, $otherFacility] = $this->inTenant($this->tenantA(), function () {
            $fac1 = Facility::factory()->create();
            $fac2 = Facility::factory()->create();

            FacilityComponent::factory()->create(['facility_id' => $fac1->id, 'name' => 'Aro 1', 'condition_status' => 'ok']);
            FacilityComponent::factory()->create(['facility_id' => $fac1->id, 'name' => 'Aro 2', 'condition_status' => 'review']);
            FacilityComponent::factory()->create(['facility_id' => $fac2->id, 'name' => 'Red', 'condition_status' => 'ok']);

            return [$fac1, $fac2];
        });

        $token = $this->token();

        // Lista componentes de facility 1
        $this->withClubToken($token)->getJson("/api/facilities/{$facility->id}/components")
            ->assertOk()
            ->assertJsonCount(2, 'data');

        // Filtrar por condition_status
        $this->withClubToken($token)->getJson("/api/facilities/{$facility->id}/components?condition_status=review")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Aro 2');
    }

    public function test_creates_component_with_condition_status_enum(): void
    {
        $facility = $this->inTenant($this->tenantA(), fn () => Facility::factory()->create());
        $token = $this->token('maintenance_manager');

        $this->withClubToken($token)->postJson("/api/facilities/{$facility->id}/components", [
            'name' => 'Tablero electrónico',
            'condition_status' => 'ok',
            'notes' => 'Instalado recientemente',
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Tablero electrónico')
            ->assertJsonPath('data.condition_status', 'ok')
            ->assertJsonPath('data.facility_id', $facility->id);

        $this->assertTrue($this->inTenant($this->tenantA(), fn () => FacilityComponent::where('name', 'Tablero electrónico')->exists()));

        // Validación de enum: solo ok, review, out_of_service
        $this->withClubToken($token)->postJson("/api/facilities/{$facility->id}/components", [
            'name' => 'Red rota',
            'condition_status' => 'estado_invalido',
        ])->assertUnprocessable()->assertJsonValidationErrors('condition_status');
    }

    public function test_shows_component(): void
    {
        $component = $this->inTenant($this->tenantA(), function () {
            $fac = Facility::factory()->create(['name' => 'Cancha Principal']);

            return FacilityComponent::factory()->create([
                'facility_id' => $fac->id,
                'name' => 'Iluminación LED',
                'condition_status' => 'ok',
            ]);
        });

        $this->withClubToken($this->token())->getJson("/api/components/{$component->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Iluminación LED')
            ->assertJsonPath('data.condition_status', 'ok')
            ->assertJsonPath('data.facility.name', 'Cancha Principal');
    }

    public function test_updates_component_condition_status(): void
    {
        $component = $this->inTenant($this->tenantA(), fn () => FacilityComponent::factory()->create([
            'condition_status' => 'ok',
        ]));

        $this->withClubToken($this->token('maintenance_manager'))->patchJson("/api/components/{$component->id}", [
            'condition_status' => 'out_of_service',
            'notes' => 'Aro quebrado tras entrenamiento',
        ])
            ->assertOk()
            ->assertJsonPath('data.condition_status', 'out_of_service')
            ->assertJsonPath('data.notes', 'Aro quebrado tras entrenamiento');

        $this->assertSame('out_of_service', $this->inTenant($this->tenantA(), fn () => FacilityComponent::find($component->id)->condition_status));
    }

    public function test_delete_is_real_delete_not_soft(): void
    {
        $component = $this->inTenant($this->tenantA(), fn () => FacilityComponent::factory()->create());
        $token = $this->token('maintenance_manager');

        $this->withClubToken($token)->deleteJson("/api/components/{$component->id}")->assertNoContent();
        $this->withClubToken($token)->getJson("/api/components/{$component->id}")->assertNotFound();

        // Borrado real: no existe en la base de datos
        $this->assertNull($this->inTenant($this->tenantA(), fn () => FacilityComponent::find($component->id)));
    }

    public function test_component_of_another_club_is_not_accessible(): void
    {
        $componentB = $this->inTenant($this->tenantB(), fn () => FacilityComponent::factory()->create(['name' => 'Aro Club B']));
        $token = $this->token();

        $this->withClubToken($token)->getJson("/api/components/{$componentB->id}")->assertNotFound();
        $this->withClubToken($token)->patchJson("/api/components/{$componentB->id}", ['condition_status' => 'review'])->assertNotFound();
        $this->withClubToken($token)->deleteJson("/api/components/{$componentB->id}")->assertNotFound();
    }
}
