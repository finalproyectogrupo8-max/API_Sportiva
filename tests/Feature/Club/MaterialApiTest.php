<?php

namespace Tests\Feature\Club;

use App\Models\Club\InventoryBalance;
use App\Models\Club\Material;
use App\Models\Club\StorageLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** ABM de materiales (materials): los 10 casos de docs/guia-abm.md §11. */
class MaterialApiTest extends TestCase
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
            Material::factory()->count(22)->sequence(fn ($s) => [
                'name' => 'Material '.str_pad((string) $s->index, 2, '0', STR_PAD_LEFT),
                'unit' => 'unidades',
            ])->create();
            Material::factory()->create(['name' => 'Conos Viejos', 'is_active' => false, 'unit' => 'conos']);
        });
        $token = $this->token();

        $this->withClubToken($token)->getJson('/api/materials')
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.total', 23)
            ->assertJsonStructure(['data', 'links', 'meta']);

        $this->withClubToken($token)->getJson('/api/materials?is_active=0')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Conos Viejos');

        $this->withClubToken($token)->getJson('/api/materials?unit=conos')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.unit', 'conos');

        $this->withClubToken($token)->getJson('/api/materials?search=Material 0&sort=-name')
            ->assertOk()->assertJsonCount(10, 'data')->assertJsonPath('data.0.name', 'Material 09');

        $this->withClubToken($token)->getJson('/api/materials?sort=invalid')->assertUnprocessable();
    }

    public function test_shows_one(): void
    {
        $material = $this->inTenant($this->tenantA(), fn () => Material::factory()->create([
            'name' => 'Pelotas de Fútbol N° 5',
            'unit' => 'pelotas',
            'minimum_stock' => 15.00,
        ]));

        $this->withClubToken($this->token())->getJson("/api/materials/{$material->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Pelotas de Fútbol N° 5')
            ->assertJsonPath('data.unit', 'pelotas')
            ->assertJsonPath('data.minimum_stock', fn ($v) => (float) $v == 15.0)
            ->assertJsonPath('data.is_active', true);
    }

    public function test_creates_and_validates(): void
    {
        $token = $this->token();

        $this->withClubToken($token)->postJson('/api/materials', [
            'name' => 'Pecheras Rojas',
            'description' => 'Talle único',
            'unit' => 'unidades',
            'minimum_stock' => 20,
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Pecheras Rojas')
            ->assertJsonPath('data.unit', 'unidades')
            ->assertJsonPath('data.minimum_stock', fn ($v) => (float) $v == 20.0)
            ->assertJsonPath('data.is_active', true);

        $this->assertTrue($this->inTenant($this->tenantA(), fn () => Material::where('name', 'Pecheras Rojas')->exists()));

        // Duplicado de nombre falla con 422
        $this->withClubToken($token)->postJson('/api/materials', [
            'name' => 'Pecheras Rojas',
            'unit' => 'unidades',
        ])->assertUnprocessable()->assertJsonValidationErrors('name');

        // Falta unit o nombre vacío
        $this->withClubToken($token)->postJson('/api/materials', [
            'name' => '',
            'unit' => '',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'unit']);

        // Stock mínimo negativo falla con 422
        $this->withClubToken($token)->postJson('/api/materials', [
            'name' => 'Vallas',
            'unit' => 'unidades',
            'minimum_stock' => -5,
        ])->assertUnprocessable()->assertJsonValidationErrors('minimum_stock');
    }

    public function test_updates_only_what_was_sent(): void
    {
        $material = $this->inTenant($this->tenantA(), fn () => Material::factory()->create([
            'name' => 'Conos Tortuga',
            'unit' => 'conos',
            'minimum_stock' => 30,
            'is_active' => true,
        ]));

        $this->withClubToken($this->token())->patchJson("/api/materials/{$material->id}", [
            'minimum_stock' => 45,
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Conos Tortuga')
            ->assertJsonPath('data.unit', 'conos')
            ->assertJsonPath('data.minimum_stock', fn ($v) => (float) $v == 45.0);
    }

    public function test_delete_is_soft(): void
    {
        $material = $this->inTenant($this->tenantA(), fn () => Material::factory()->create());
        $token = $this->token();

        $this->withClubToken($token)->deleteJson("/api/materials/{$material->id}")->assertNoContent();
        $this->withClubToken($token)->getJson("/api/materials/{$material->id}")->assertNotFound();
        $this->assertNotNull($this->inTenant($this->tenantA(), fn () => Material::withTrashed()->find($material->id)?->deleted_at));
    }

    public function test_delete_conflicts_when_has_stock(): void
    {
        $material = $this->inTenant($this->tenantA(), function () {
            $mat = Material::factory()->create();
            $loc = StorageLocation::factory()->create();
            InventoryBalance::factory()->create([
                'material_id' => $mat->id,
                'location_id' => $loc->id,
                'quantity' => 10,
            ]);

            return $mat;
        });

        $this->withClubToken($this->token())->deleteJson("/api/materials/{$material->id}")
            ->assertConflict()
            ->assertJsonPath('code', 'resource_in_use');

        $this->assertNull($this->inTenant($this->tenantA(), fn () => Material::find($material->id)?->deleted_at));
    }

    public function test_requires_token_and_permission(): void
    {
        $this->getJson('/api/materials')->assertUnauthorized();

        // El profe no tiene materials.*
        $token = $this->token('coach');
        $this->withClubToken($token)->getJson('/api/materials')->assertForbidden()->assertJsonPath('code', 'forbidden');
        $this->withClubToken($token)->postJson('/api/materials', [
            'name' => 'Pelotas',
            'unit' => 'unidades',
        ])->assertForbidden();

        // El utilero sí tiene permisos de materiales
        $utileroToken = $this->token('inventory_manager');
        $this->withClubToken($utileroToken)->getJson('/api/materials')->assertOk();
    }

    public function test_missing_returns_404(): void
    {
        $this->withClubToken($this->token())->getJson('/api/materials/999999')->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_material_of_another_club_is_not_visible(): void
    {
        $materialB = $this->inTenant($this->tenantB(), fn () => Material::factory()->create(['name' => 'Pelota Club B']));
        $token = $this->token();

        $this->withClubToken($token)->getJson("/api/materials/{$materialB->id}")->assertNotFound();
        $this->withClubToken($token)->patchJson("/api/materials/{$materialB->id}", ['name' => 'X'])->assertNotFound();
        $this->withClubToken($token)->deleteJson("/api/materials/{$materialB->id}")->assertNotFound();
        $this->withClubToken($token)->getJson('/api/materials')->assertJsonCount(0, 'data');

        $this->assertSame('Pelota Club B', $this->inTenant($this->tenantB(), fn () => Material::find($materialB->id)->name));
    }
}
