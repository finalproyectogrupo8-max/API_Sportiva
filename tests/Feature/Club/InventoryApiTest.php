<?php

namespace Tests\Feature\Club;

use App\Models\Club\InventoryBalance;
use App\Models\Club\InventoryMovement;
use App\Models\Club\Material;
use App\Models\Club\StorageLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** Tests de inventario (balances, movimientos, transferencias, concurrencia y permisos). */
class InventoryApiTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    private function token(string $role = 'admin'): string
    {
        return $this->clubToken($this->tenantA(), roles: [$role]);
    }

    public function test_lists_balances_paginated_and_filtered(): void
    {
        [$loc1, $loc2, $mat1, $mat2] = $this->inTenant($this->tenantA(), function () {
            $loc1 = StorageLocation::factory()->create(['name' => 'Depósito 1']);
            $loc2 = StorageLocation::factory()->create(['name' => 'Depósito 2']);
            $mat1 = Material::factory()->create(['name' => 'Pelotas']);
            $mat2 = Material::factory()->create(['name' => 'Conos']);

            InventoryBalance::factory()->create(['location_id' => $loc1->id, 'material_id' => $mat1->id, 'quantity' => 15]);
            InventoryBalance::factory()->create(['location_id' => $loc1->id, 'material_id' => $mat2->id, 'quantity' => 20]);
            InventoryBalance::factory()->create(['location_id' => $loc2->id, 'material_id' => $mat1->id, 'quantity' => 5]);

            return [$loc1, $loc2, $mat1, $mat2];
        });

        $token = $this->token('inventory_manager');

        $this->withClubToken($token)->getJson('/api/inventory/balances')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data', 'links', 'meta']);

        // Filtrar por depósito
        $this->withClubToken($token)->getJson("/api/inventory/balances?location_id={$loc2->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.quantity', fn ($v) => (float) $v == 5.0);

        // Filtrar por material
        $this->withClubToken($token)->getJson("/api/inventory/balances?material_id={$mat2->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.material_id', $mat2->id);
    }

    public function test_lists_balances_filtered_by_below_minimum(): void
    {
        [$loc, $matBelow, $matOk] = $this->inTenant($this->tenantA(), function () {
            $loc = StorageLocation::factory()->create();
            $matBelow = Material::factory()->create(['name' => 'Pelotas', 'minimum_stock' => 10]);
            $matOk = Material::factory()->create(['name' => 'Conos', 'minimum_stock' => 5]);

            // matBelow tiene 3 (menor a 10)
            InventoryBalance::factory()->create(['location_id' => $loc->id, 'material_id' => $matBelow->id, 'quantity' => 3]);
            // matOk tiene 8 (mayor o igual a 5)
            InventoryBalance::factory()->create(['location_id' => $loc->id, 'material_id' => $matOk->id, 'quantity' => 8]);

            return [$loc, $matBelow, $matOk];
        });

        $token = $this->token('inventory_manager');

        $this->withClubToken($token)->getJson('/api/inventory/balances?below_minimum=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.material_id', $matBelow->id)
            ->assertJsonPath('data.0.is_below_minimum', true);
    }

    public function test_inbound_and_outbound_movements_calculate_balance_correctly(): void
    {
        // Criterio de aceptación: Un ingreso de 10 y un egreso de 3 dejan el balance en 7.
        [$loc, $mat] = $this->inTenant($this->tenantA(), function () {
            return [
                StorageLocation::factory()->create(),
                Material::factory()->create(),
            ];
        });

        $token = $this->token('inventory_manager');

        // 1. Ingreso de 10
        $this->withClubToken($token)->postJson('/api/inventory/movements', [
            'material_id' => $mat->id,
            'location_id' => $loc->id,
            'movement_type' => 'in',
            'quantity' => 10,
            'reason' => 'Compra inicial',
            'reference' => 'FAC-001',
        ])
            ->assertCreated()
            ->assertJsonPath('data.movement_type', 'in')
            ->assertJsonPath('data.quantity', fn ($v) => (float) $v == 10.0);

        $balanceAfterIn = $this->inTenant($this->tenantA(), fn () => InventoryBalance::where('material_id', $mat->id)->where('location_id', $loc->id)->first());
        $this->assertNotNull($balanceAfterIn);
        $this->assertEquals(10.0, (float) $balanceAfterIn->quantity);

        // 2. Egreso de 3
        $this->withClubToken($token)->postJson('/api/inventory/movements', [
            'material_id' => $mat->id,
            'location_id' => $loc->id,
            'movement_type' => 'out',
            'quantity' => 3,
            'reason' => 'Entrega a primera división',
            'reference' => 'REM-001',
        ])
            ->assertCreated()
            ->assertJsonPath('data.movement_type', 'out')
            ->assertJsonPath('data.quantity', fn ($v) => (float) $v == 3.0);

        // Balance resultante es 7
        $balanceAfterOut = $this->inTenant($this->tenantA(), fn () => InventoryBalance::where('material_id', $mat->id)->where('location_id', $loc->id)->first());
        $this->assertEquals(7.0, (float) $balanceAfterOut->quantity);

        // Se crearon 2 movimientos en el libro
        $movementsCount = $this->inTenant($this->tenantA(), fn () => InventoryMovement::where('material_id', $mat->id)->count());
        $this->assertSame(2, $movementsCount);
    }

    public function test_outbound_exceeding_stock_returns_409_insufficient_stock(): void
    {
        // Criterio de aceptación: Un egreso superior al stock devuelve 409 insufficient_stock.
        [$loc, $mat] = $this->inTenant($this->tenantA(), function () {
            $loc = StorageLocation::factory()->create();
            $mat = Material::factory()->create();
            InventoryBalance::factory()->create(['location_id' => $loc->id, 'material_id' => $mat->id, 'quantity' => 5]);

            return [$loc, $mat];
        });

        $token = $this->token('inventory_manager');

        // Intenta egresar 8 teniendo 5
        $this->withClubToken($token)->postJson('/api/inventory/movements', [
            'material_id' => $mat->id,
            'location_id' => $loc->id,
            'movement_type' => 'out',
            'quantity' => 8,
        ])
            ->assertConflict()
            ->assertJsonPath('code', 'insufficient_stock');

        // El balance sigue intacto en 5 y no se creó ningún movimiento
        $balance = $this->inTenant($this->tenantA(), fn () => InventoryBalance::where('material_id', $mat->id)->where('location_id', $loc->id)->first());
        $this->assertEquals(5.0, (float) $balance->quantity);
        $this->assertSame(0, $this->inTenant($this->tenantA(), fn () => InventoryMovement::count()));
    }

    public function test_adjustment_positive_and_negative_sign_d14(): void
    {
        // Decisión D14: cantidad con signo (positivo suma, negativo resta, no cero).
        [$loc, $mat] = $this->inTenant($this->tenantA(), function () {
            $loc = StorageLocation::factory()->create();
            $mat = Material::factory()->create();
            InventoryBalance::factory()->create(['location_id' => $loc->id, 'material_id' => $mat->id, 'quantity' => 10]);

            return [$loc, $mat];
        });

        $token = $this->token('inventory_manager');

        // Ajuste positivo: +5 deja balance en 15
        $this->withClubToken($token)->postJson('/api/inventory/movements', [
            'material_id' => $mat->id,
            'location_id' => $loc->id,
            'movement_type' => 'adjustment',
            'quantity' => 5,
            'reason' => 'Recuento físico encontró sobrante',
        ])
            ->assertCreated()
            ->assertJsonPath('data.quantity', fn ($v) => (float) $v == 5.0);

        $this->assertEquals(15.0, (float) $this->inTenant($this->tenantA(), fn () => InventoryBalance::where('material_id', $mat->id)->first()->quantity));

        // Ajuste negativo: -4 deja balance en 11
        $this->withClubToken($token)->postJson('/api/inventory/movements', [
            'material_id' => $mat->id,
            'location_id' => $loc->id,
            'movement_type' => 'adjustment',
            'quantity' => -4,
            'reason' => 'Rotura o faltante verificado',
        ])
            ->assertCreated()
            ->assertJsonPath('data.quantity', fn ($v) => (float) $v == -4.0);

        $this->assertEquals(11.0, (float) $this->inTenant($this->tenantA(), fn () => InventoryBalance::where('material_id', $mat->id)->first()->quantity));

        // Ajuste negativo mayor al stock devuelve 409
        $this->withClubToken($token)->postJson('/api/inventory/movements', [
            'material_id' => $mat->id,
            'location_id' => $loc->id,
            'movement_type' => 'adjustment',
            'quantity' => -20,
        ])
            ->assertConflict()
            ->assertJsonPath('code', 'insufficient_stock');

        // Ajuste con cantidad 0 es inválido (422)
        $this->withClubToken($token)->postJson('/api/inventory/movements', [
            'material_id' => $mat->id,
            'location_id' => $loc->id,
            'movement_type' => 'adjustment',
            'quantity' => 0,
        ])->assertUnprocessable()->assertJsonValidationErrors('quantity');
    }

    public function test_lists_movements_paginated_and_filtered(): void
    {
        [$loc1, $loc2, $mat] = $this->inTenant($this->tenantA(), function () {
            $loc1 = StorageLocation::factory()->create();
            $loc2 = StorageLocation::factory()->create();
            $mat = Material::factory()->create();

            InventoryMovement::factory()->create(['location_id' => $loc1->id, 'material_id' => $mat->id, 'movement_type' => 'in', 'created_at' => now()->subMinutes(10)]);
            InventoryMovement::factory()->create(['location_id' => $loc1->id, 'material_id' => $mat->id, 'movement_type' => 'out', 'created_at' => now()->subMinutes(5)]);
            InventoryMovement::factory()->create(['location_id' => $loc2->id, 'material_id' => $mat->id, 'movement_type' => 'in', 'created_at' => now()]);

            return [$loc1, $loc2, $mat];
        });

        $token = $this->token('inventory_manager');

        $this->withClubToken($token)->getJson('/api/inventory/movements')
            ->assertOk()
            ->assertJsonCount(3, 'data');

        // Filtrar por movement_type
        $this->withClubToken($token)->getJson('/api/inventory/movements?movement_type=out')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.movement_type', 'out');

        // Filtrar por depósito
        $this->withClubToken($token)->getJson("/api/inventory/movements?location_id={$loc2->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_movements_book_is_immutable_without_update_or_delete_routes(): void
    {
        $movement = $this->inTenant($this->tenantA(), function () {
            $loc = StorageLocation::factory()->create();
            $mat = Material::factory()->create();

            return InventoryMovement::factory()->create([
                'location_id' => $loc->id,
                'material_id' => $mat->id,
            ]);
        });

        $token = $this->token('inventory_manager');

        // PUT, PATCH y DELETE no existen para movements
        $this->withClubToken($token)->putJson("/api/inventory/movements/{$movement->id}", ['quantity' => 99])->assertNotFound();
        $this->withClubToken($token)->patchJson("/api/inventory/movements/{$movement->id}", ['quantity' => 99])->assertNotFound();
        $this->withClubToken($token)->deleteJson("/api/inventory/movements/{$movement->id}")->assertNotFound();
    }

    public function test_transfers_stock_atomically_between_storage_locations(): void
    {
        [$fromLoc, $toLoc, $mat] = $this->inTenant($this->tenantA(), function () {
            $fromLoc = StorageLocation::factory()->create(['name' => 'Depósito Central']);
            $toLoc = StorageLocation::factory()->create(['name' => 'Utilería Cancha 1']);
            $mat = Material::factory()->create();

            InventoryBalance::factory()->create(['location_id' => $fromLoc->id, 'material_id' => $mat->id, 'quantity' => 10]);

            return [$fromLoc, $toLoc, $mat];
        });

        $token = $this->token('inventory_manager');

        // Transfiere 4 unidades de fromLoc a toLoc
        $response = $this->withClubToken($token)->postJson('/api/inventory/transfers', [
            'material_id' => $mat->id,
            'from_location_id' => $fromLoc->id,
            'to_location_id' => $toLoc->id,
            'quantity' => 4,
            'reason' => 'Distribución para entrenamiento',
            'reference' => 'TRF-100',
        ])
            ->assertCreated()
            ->assertJsonPath('data.quantity', fn ($v) => (float) $v == 4.0)
            ->assertJsonPath('data.from_location_id', $fromLoc->id)
            ->assertJsonPath('data.to_location_id', $toLoc->id)
            ->assertJsonStructure(['data' => ['transfer_group_id', 'movements']]);

        $transferGroupId = $response->json('data.transfer_group_id');
        $this->assertNotEmpty($transferGroupId);

        // Balances actualizados correctamente
        $this->assertEquals(6.0, (float) $this->inTenant($this->tenantA(), fn () => InventoryBalance::where('material_id', $mat->id)->where('location_id', $fromLoc->id)->first()->quantity));
        $this->assertEquals(4.0, (float) $this->inTenant($this->tenantA(), fn () => InventoryBalance::where('material_id', $mat->id)->where('location_id', $toLoc->id)->first()->quantity));

        // Dos movimientos generados con el mismo transfer_group_id
        $movements = $this->inTenant($this->tenantA(), fn () => InventoryMovement::where('transfer_group_id', $transferGroupId)->get());
        $this->assertCount(2, $movements);
        $this->assertTrue($movements->contains('movement_type', 'transfer_out'));
        $this->assertTrue($movements->contains('movement_type', 'transfer_in'));
    }

    public function test_transfer_fails_atomically_when_insufficient_stock(): void
    {
        // Criterio de aceptación: Si una pata falla, no aplica ninguna
        [$fromLoc, $toLoc, $mat] = $this->inTenant($this->tenantA(), function () {
            $fromLoc = StorageLocation::factory()->create();
            $toLoc = StorageLocation::factory()->create();
            $mat = Material::factory()->create();

            InventoryBalance::factory()->create(['location_id' => $fromLoc->id, 'material_id' => $mat->id, 'quantity' => 3]);
            InventoryBalance::factory()->create(['location_id' => $toLoc->id, 'material_id' => $mat->id, 'quantity' => 1]);

            return [$fromLoc, $toLoc, $mat];
        });

        $token = $this->token('inventory_manager');

        // Intenta transferir 5 teniendo solo 3
        $this->withClubToken($token)->postJson('/api/inventory/transfers', [
            'material_id' => $mat->id,
            'from_location_id' => $fromLoc->id,
            'to_location_id' => $toLoc->id,
            'quantity' => 5,
        ])
            ->assertConflict()
            ->assertJsonPath('code', 'insufficient_stock');

        // Ningún saldo cambió
        $this->assertEquals(3.0, (float) $this->inTenant($this->tenantA(), fn () => InventoryBalance::where('material_id', $mat->id)->where('location_id', $fromLoc->id)->first()->quantity));
        $this->assertEquals(1.0, (float) $this->inTenant($this->tenantA(), fn () => InventoryBalance::where('material_id', $mat->id)->where('location_id', $toLoc->id)->first()->quantity));

        // Ningún movimiento fue insertado
        $this->assertSame(0, $this->inTenant($this->tenantA(), fn () => InventoryMovement::count()));
    }

    public function test_transfer_to_same_location_is_rejected(): void
    {
        [$loc, $mat] = $this->inTenant($this->tenantA(), function () {
            return [
                StorageLocation::factory()->create(),
                Material::factory()->create(),
            ];
        });

        $this->withClubToken($this->token('inventory_manager'))->postJson('/api/inventory/transfers', [
            'material_id' => $mat->id,
            'from_location_id' => $loc->id,
            'to_location_id' => $loc->id,
            'quantity' => 2,
        ])->assertUnprocessable()->assertJsonValidationErrors('to_location_id');
    }

    public function test_requires_token_and_permissions(): void
    {
        $this->getJson('/api/inventory/balances')->assertUnauthorized();
        $this->getJson('/api/inventory/movements')->assertUnauthorized();
        $this->postJson('/api/inventory/movements', [])->assertUnauthorized();
        $this->postJson('/api/inventory/transfers', [])->assertUnauthorized();

        [$loc1, $loc2, $mat] = $this->inTenant($this->tenantA(), fn () => [
            StorageLocation::factory()->create(),
            StorageLocation::factory()->create(),
            Material::factory()->create(),
        ]);

        // El rol coach no tiene inventory.*
        $coachToken = $this->token('coach');
        $this->withClubToken($coachToken)->getJson('/api/inventory/balances')->assertForbidden()->assertJsonPath('code', 'forbidden');
        $this->withClubToken($coachToken)->getJson('/api/inventory/movements')->assertForbidden()->assertJsonPath('code', 'forbidden');
        $this->withClubToken($coachToken)->postJson('/api/inventory/movements', [
            'material_id' => $mat->id,
            'location_id' => $loc1->id,
            'movement_type' => 'in',
            'quantity' => 10,
        ])->assertForbidden()->assertJsonPath('code', 'forbidden');
        $this->withClubToken($coachToken)->postJson('/api/inventory/transfers', [
            'material_id' => $mat->id,
            'from_location_id' => $loc1->id,
            'to_location_id' => $loc2->id,
            'quantity' => 5,
        ])->assertForbidden()->assertJsonPath('code', 'forbidden');
    }

    public function test_tenant_isolation_on_inventory(): void
    {
        [$locB, $matB] = $this->inTenant($this->tenantB(), function () {
            $loc = StorageLocation::factory()->create();
            $mat = Material::factory()->create();
            InventoryBalance::factory()->create(['location_id' => $loc->id, 'material_id' => $mat->id, 'quantity' => 50]);
            InventoryMovement::factory()->create(['location_id' => $loc->id, 'material_id' => $mat->id, 'quantity' => 50]);

            return [$loc, $mat];
        });

        $tokenA = $this->token('inventory_manager');

        // Desde el Club A no se ven balances ni movimientos del Club B
        $this->withClubToken($tokenA)->getJson('/api/inventory/balances')->assertOk()->assertJsonCount(0, 'data');
        $this->withClubToken($tokenA)->getJson('/api/inventory/movements')->assertOk()->assertJsonCount(0, 'data');

        // No se puede crear un movimiento en Club A usando un material de Club B (falla validación exists en tenant A)
        $locA = $this->inTenant($this->tenantA(), fn () => StorageLocation::factory()->create());
        $this->withClubToken($tokenA)->postJson('/api/inventory/movements', [
            'material_id' => $matB->id,
            'location_id' => $locA->id,
            'movement_type' => 'in',
            'quantity' => 5,
        ])->assertUnprocessable()->assertJsonValidationErrors('material_id');
    }
}
