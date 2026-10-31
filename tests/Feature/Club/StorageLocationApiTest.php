<?php

namespace Tests\Feature\Club;

use App\Models\Club\InventoryBalance;
use App\Models\Club\Material;
use App\Models\Club\StorageLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** ABM de depósitos (storage_locations): los 10 casos de docs/guia-abm.md §11. */
class StorageLocationApiTest extends TestCase
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
            StorageLocation::factory()->count(22)->sequence(fn ($s) => [
                'name' => 'Depósito '.str_pad((string) $s->index, 2, '0', STR_PAD_LEFT),
                'location_code' => 'DEP-'.str_pad((string) $s->index, 2, '0', STR_PAD_LEFT),
            ])->create();
            StorageLocation::factory()->create(['name' => 'Depósito Inactivo', 'is_active' => false]);
        });
        $token = $this->token();

        $this->withClubToken($token)->getJson('/api/storage-locations')
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.total', 23)
            ->assertJsonStructure(['data', 'links', 'meta']);

        $this->withClubToken($token)->getJson('/api/storage-locations?is_active=0')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Depósito Inactivo');

        $this->withClubToken($token)->getJson('/api/storage-locations?search=Depósito 0&sort=-name')
            ->assertOk()->assertJsonCount(10, 'data')->assertJsonPath('data.0.name', 'Depósito 09');

        $this->withClubToken($token)->getJson('/api/storage-locations?sort=invalid')->assertUnprocessable();
    }

    public function test_shows_one(): void
    {
        $location = $this->inTenant($this->tenantA(), fn () => StorageLocation::factory()->create([
            'name' => 'Utilería Principal',
            'location_code' => 'UTI-01',
        ]));

        $this->withClubToken($this->token())->getJson("/api/storage-locations/{$location->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Utilería Principal')
            ->assertJsonPath('data.location_code', 'UTI-01')
            ->assertJsonPath('data.is_active', true);
    }

    public function test_creates_and_validates(): void
    {
        $token = $this->token();

        $this->withClubToken($token)->postJson('/api/storage-locations', [
            'name' => 'Depósito Norte',
            'location_code' => 'DEP-N',
            'description' => 'Sector canchas auxiliares',
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Depósito Norte')
            ->assertJsonPath('data.location_code', 'DEP-N')
            ->assertJsonPath('data.is_active', true);

        $this->assertTrue($this->inTenant($this->tenantA(), fn () => StorageLocation::where('location_code', 'DEP-N')->exists()));

        // Duplicado de location_code falla con 422
        $this->withClubToken($token)->postJson('/api/storage-locations', [
            'name' => 'Otro Depósito',
            'location_code' => 'DEP-N',
        ])->assertUnprocessable()->assertJsonValidationErrors('location_code');

        // Nombre vacío falla con 422
        $this->withClubToken($token)->postJson('/api/storage-locations', [
            'name' => '',
        ])->assertUnprocessable()->assertJsonValidationErrors('name');
    }

    public function test_updates_only_what_was_sent(): void
    {
        $location = $this->inTenant($this->tenantA(), fn () => StorageLocation::factory()->create([
            'name' => 'Depósito Sur',
            'location_code' => 'DEP-S',
            'description' => 'Original',
            'is_active' => true,
        ]));

        $this->withClubToken($this->token())->patchJson("/api/storage-locations/{$location->id}", [
            'name' => 'Depósito Sur Ampliado',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Depósito Sur Ampliado')
            ->assertJsonPath('data.location_code', 'DEP-S')
            ->assertJsonPath('data.description', 'Original');
    }

    public function test_delete_is_soft(): void
    {
        $location = $this->inTenant($this->tenantA(), fn () => StorageLocation::factory()->create());
        $token = $this->token();

        $this->withClubToken($token)->deleteJson("/api/storage-locations/{$location->id}")->assertNoContent();
        $this->withClubToken($token)->getJson("/api/storage-locations/{$location->id}")->assertNotFound();
        $this->assertNotNull($this->inTenant($this->tenantA(), fn () => StorageLocation::withTrashed()->find($location->id)?->deleted_at));
    }

    public function test_delete_conflicts_when_has_stock(): void
    {
        $location = $this->inTenant($this->tenantA(), function () {
            $loc = StorageLocation::factory()->create();
            $material = Material::factory()->create();
            InventoryBalance::factory()->create([
                'location_id' => $loc->id,
                'material_id' => $material->id,
                'quantity' => 5,
            ]);

            return $loc;
        });

        $this->withClubToken($this->token())->deleteJson("/api/storage-locations/{$location->id}")
            ->assertConflict()
            ->assertJsonPath('code', 'resource_in_use');

        $this->assertNull($this->inTenant($this->tenantA(), fn () => StorageLocation::find($location->id)?->deleted_at));
    }

    public function test_requires_token_and_permission(): void
    {
        $this->getJson('/api/storage-locations')->assertUnauthorized();

        // El profe no tiene storage_locations.*
        $token = $this->token('coach');
        $this->withClubToken($token)->getJson('/api/storage-locations')->assertForbidden()->assertJsonPath('code', 'forbidden');
        $this->withClubToken($token)->postJson('/api/storage-locations', ['name' => 'Nuevo'])->assertForbidden();

        // El utilero sí tiene permisos de depósitos
        $utileroToken = $this->token('inventory_manager');
        $this->withClubToken($utileroToken)->getJson('/api/storage-locations')->assertOk();
    }

    public function test_missing_returns_404(): void
    {
        $this->withClubToken($this->token())->getJson('/api/storage-locations/999999')->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_storage_location_of_another_club_is_not_visible(): void
    {
        $locationB = $this->inTenant($this->tenantB(), fn () => StorageLocation::factory()->create(['name' => 'Depósito Club B']));
        $token = $this->token();

        $this->withClubToken($token)->getJson("/api/storage-locations/{$locationB->id}")->assertNotFound();
        $this->withClubToken($token)->patchJson("/api/storage-locations/{$locationB->id}", ['name' => 'X'])->assertNotFound();
        $this->withClubToken($token)->deleteJson("/api/storage-locations/{$locationB->id}")->assertNotFound();
        $this->withClubToken($token)->getJson('/api/storage-locations')->assertJsonCount(0, 'data');

        $this->assertSame('Depósito Club B', $this->inTenant($this->tenantB(), fn () => StorageLocation::find($locationB->id)->name));
    }
}
