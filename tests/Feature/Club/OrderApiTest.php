<?php

namespace Tests\Feature\Club;

use App\Models\Club\ApparelItem;
use App\Models\Club\Category;
use App\Models\Club\CategoryCoach;
use App\Models\Club\ClubUser;
use App\Models\Club\Coach;
use App\Models\Club\InventoryBalance;
use App\Models\Club\InventoryMovement;
use App\Models\Club\Material;
use App\Models\Club\Order;
use App\Models\Club\OrderStatusHistory;
use App\Models\Club\Player;
use App\Models\Club\Role;
use App\Models\Club\StorageLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** Tests del circuito de indumentaria y pedidos (TIZ-18). */
class OrderApiTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    private function token(string $role = 'admin'): string
    {
        return $this->clubToken($this->tenantA(), roles: [$role]);
    }

    public function test_full_circuit_draft_submitted_approved_delivered_with_status_history(): void
    {
        [$player1, $player2, $apparel] = $this->inTenant($this->tenantA(), function () {
            $p1 = Player::factory()->create(['first_name' => 'Lucas', 'last_name' => 'Gomez']);
            $p2 = Player::factory()->create(['first_name' => 'Mateo', 'last_name' => 'Perez']);
            $app = ApparelItem::factory()->create(['name' => 'Conjunto Deportivo']);

            return [$p1, $p2, $app];
        });

        $adminToken = $this->token('admin');

        // 1. Crear pedido en estado draft
        $createRes = $this->withClubToken($adminToken)->postJson('/api/orders', [
            'type' => 'apparel',
            'priority' => 'high',
            'description' => 'Indumentaria temporada 2026',
            'apparel_order_items' => [
                [
                    'player_id' => $player1->id,
                    'apparel_item_id' => $apparel->id,
                    'size' => 'M',
                    'shirt_number' => 10,
                    'alias' => 'Luki',
                    'quantity' => 1,
                ],
                [
                    'player_id' => $player2->id,
                    'apparel_item_id' => $apparel->id,
                    'size' => 'L',
                    'shirt_number' => 5,
                    'alias' => 'Matu',
                    'quantity' => 1,
                ],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.type', 'apparel')
            ->assertJsonPath('data.priority', 'high')
            ->assertJsonCount(2, 'data.apparel_order_items')
            ->assertJsonCount(1, 'data.status_history');

        $orderId = $createRes->json('data.id');
        $item1Id = $createRes->json('data.apparel_order_items.0.id');
        $item2Id = $createRes->json('data.apparel_order_items.1.id');

        // 2. Editar en draft (cambiar alias o descripción)
        $this->withClubToken($adminToken)->putJson("/api/orders/{$orderId}", [
            'description' => 'Indumentaria oficial 2026 corregida',
        ])
            ->assertOk()
            ->assertJsonPath('data.description', 'Indumentaria oficial 2026 corregida');

        // 3. Enviar (submit)
        $this->withClubToken($adminToken)->postJson("/api/orders/{$orderId}/submit", [
            'comment' => 'Pedido revisado y enviado para aprobación',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'submitted');

        // 4. Aprobar (approve)
        $approveRes = $this->withClubToken($adminToken)->postJson("/api/orders/{$orderId}/approve", [
            'comment' => 'Aprobado por administración',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');
        $this->assertNotNull($approveRes->json('data.approved_at'));

        // 5. Entrega parcial (entregar item 1)
        $this->withClubToken($adminToken)->postJson("/api/orders/{$orderId}/deliveries", [
            'deliveries' => [
                ['id' => $item1Id, 'quantity' => 1],
            ],
            'comment' => 'Entregado a Lucas Gomez',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'partially_delivered')
            ->assertJsonPath('data.apparel_order_items.0.delivered_quantity', 1)
            ->assertJsonPath('data.apparel_order_items.1.delivered_quantity', 0);

        // 6. Entrega final (entregar item 2)
        $finalDelivRes = $this->withClubToken($adminToken)->postJson("/api/orders/{$orderId}/deliveries", [
            'deliveries' => [
                ['id' => $item2Id, 'quantity' => 1],
            ],
            'comment' => 'Entregado a Mateo Perez',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'delivered')
            ->assertJsonPath('data.apparel_order_items.1.delivered_quantity', 1);
        $this->assertNotNull($finalDelivRes->json('data.delivered_at'));

        // Verificar el historial completo en base de datos
        $this->inTenant($this->tenantA(), function () use ($orderId) {
            $history = OrderStatusHistory::where('order_id', $orderId)->orderBy('id')->get();
            $this->assertCount(5, $history);
            $this->assertSame('draft', $history[0]->new_status);
            $this->assertSame('submitted', $history[1]->new_status);
            $this->assertSame('approved', $history[2]->new_status);
            $this->assertSame('partially_delivered', $history[3]->new_status);
            $this->assertSame('delivered', $history[4]->new_status);
        });
    }

    public function test_reject_order_requires_reason_and_transitions_to_rejected(): void
    {
        [$order, $adminToken] = $this->inTenant($this->tenantA(), function () {
            $ord = Order::factory()->create(['status' => Order::STATUS_SUBMITTED]);

            return [$ord, $this->token('admin')];
        });

        // Sin motivo falla validación (HTTP 422)
        $this->withClubToken($adminToken)->postJson("/api/orders/{$order->id}/reject", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reason');

        // Con motivo rechaza exitosamente
        $this->withClubToken($adminToken)->postJson("/api/orders/{$order->id}/reject", [
            'reason' => 'Sin presupuesto para indumentaria este trimestre.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $this->inTenant($this->tenantA(), function () use ($order) {
            $this->assertSame('rejected', $order->fresh()->status);
            $lastHistory = OrderStatusHistory::where('order_id', $order->id)->latest('id')->first();
            $this->assertSame('rejected', $lastHistory->new_status);
            $this->assertSame('Sin presupuesto para indumentaria este trimestre.', $lastHistory->comment);
        });
    }

    public function test_material_order_delivery_discounts_inventory_stock_and_records_movement(): void
    {
        [$location, $material, $order, $item] = $this->inTenant($this->tenantA(), function () {
            $loc = StorageLocation::factory()->create(['name' => 'Depósito Central']);
            $mat = Material::factory()->create(['name' => 'Conos de Entrenamiento']);
            InventoryBalance::factory()->create([
                'location_id' => $loc->id,
                'material_id' => $mat->id,
                'quantity' => 20,
            ]);

            $ord = Order::factory()->create([
                'type' => Order::TYPE_MATERIAL,
                'status' => Order::STATUS_APPROVED,
            ]);

            $it = $ord->materialOrderItems()->create([
                'material_id' => $mat->id,
                'quantity' => 10,
                'delivered_quantity' => 0,
            ]);

            return [$loc, $mat, $ord, $it];
        });

        $token = $this->token('inventory_manager');

        // Entregar 6 conos desde el depósito
        $this->withClubToken($token)->postJson("/api/orders/{$order->id}/deliveries", [
            'location_id' => $location->id,
            'deliveries' => [
                ['id' => $item->id, 'quantity' => 6],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'partially_delivered')
            ->assertJsonPath('data.material_order_items.0.delivered_quantity', 6);

        // Verificar descuento en saldo y registro en movimientos
        $this->inTenant($this->tenantA(), function () use ($location, $material, $order) {
            $balance = InventoryBalance::where('location_id', $location->id)
                ->where('material_id', $material->id)
                ->first();
            $this->assertEquals(14, (float) $balance->quantity);

            $movement = InventoryMovement::where('order_id', $order->id)->first();
            $this->assertNotNull($movement);
            $this->assertSame('out', $movement->movement_type);
            $this->assertEquals(6, (float) $movement->quantity);
        });

        // Intentar entregar una cantidad que supera el saldo disponible (quedan 14 en stock)
        // Pedido pide 10 en total, ya se entregaron 6, quedan 4. Si intentara superar la cantidad pedida da 422:
        $this->withClubToken($token)->postJson("/api/orders/{$order->id}/deliveries", [
            'location_id' => $location->id,
            'deliveries' => [
                ['id' => $item->id, 'quantity' => 5],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'exceeds_ordered_quantity');
    }

    public function test_cannot_edit_or_delete_order_not_in_draft_returns_409(): void
    {
        $order = $this->inTenant($this->tenantA(), fn () => Order::factory()->create([
            'status' => Order::STATUS_SUBMITTED,
        ]));

        $token = $this->token('admin');

        $this->withClubToken($token)->putJson("/api/orders/{$order->id}", [
            'description' => 'Intento de edición',
        ])
            ->assertConflict()
            ->assertJsonPath('code', 'order_not_draft');

        $this->withClubToken($token)->deleteJson("/api/orders/{$order->id}")
            ->assertConflict()
            ->assertJsonPath('code', 'order_not_draft');
    }

    public function test_cannot_approve_unsubmitted_order_returns_409(): void
    {
        $order = $this->inTenant($this->tenantA(), fn () => Order::factory()->create([
            'status' => Order::STATUS_DRAFT,
        ]));

        $this->withClubToken($this->token('admin'))
            ->postJson("/api/orders/{$order->id}/approve")
            ->assertConflict()
            ->assertJsonPath('code', 'order_not_submitted');
    }

    public function test_permissions_approve_or_deliver_without_permission_returns_403(): void
    {
        $order = $this->inTenant($this->tenantA(), fn () => Order::factory()->create([
            'status' => Order::STATUS_SUBMITTED,
        ]));

        $coachToken = $this->token('coach');

        // Profe no puede aprobar pedidos
        $this->withClubToken($coachToken)->postJson("/api/orders/{$order->id}/approve")
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden');

        // Profe no puede registrar entregas
        $this->withClubToken($coachToken)->postJson("/api/orders/{$order->id}/deliveries", [])
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden');
    }

    public function test_coach_scoped_permission_only_sees_and_creates_orders_for_own_categories(): void
    {
        [$coachUser, $cat1, $cat2, $apparel, $player1, $player2] = $this->inTenant($this->tenantA(), function () {
            $user = User::factory()->create();
            $clubUser = ClubUser::create([
                'global_user_id' => $user->id,
                'display_name' => 'Profe Marcelo',
                'status' => 'active',
            ]);
            $coachRole = Role::where('slug', 'coach')->first();
            $clubUser->roles()->attach($coachRole->id);

            $c1 = Category::factory()->create(['name' => 'Sub-15']);
            $c2 = Category::factory()->create(['name' => 'Sub-17']);

            $coachModel = Coach::create([
                'club_user_id' => $clubUser->id,
                'first_name' => 'Marcelo',
                'last_name' => 'Bielsa',
                'status' => 'active',
            ]);

            CategoryCoach::create([
                'category_id' => $c1->id,
                'coach_id' => $coachModel->id,
                'assignment_type' => 'head_coach',
            ]);

            $app = ApparelItem::factory()->create();
            $p1 = Player::factory()->create();
            $p2 = Player::factory()->create();

            // Pedido existente en categoría 1
            $ord1 = Order::factory()->create([
                'category_id' => $c1->id,
                'requested_by' => $clubUser->id,
            ]);

            $otherUser = User::factory()->create();
            $otherClubUser = ClubUser::create([
                'global_user_id' => $otherUser->id,
                'display_name' => 'Otro Usuario',
                'status' => 'active',
            ]);

            // Pedido existente en categoría 2 (otra categoría)
            $ord2 = Order::factory()->create([
                'category_id' => $c2->id,
                'requested_by' => $otherClubUser->id,
            ]);

            return [$user, $c1, $c2, $app, $p1, $p2];
        });

        $coachToken = $this->clubToken($this->tenantA(), $coachUser, roles: ['coach']);

        // 1. En el listado solo ve el pedido de su categoría
        $this->withClubToken($coachToken)->getJson('/api/orders')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category_id', $cat1->id);

        // 2. Intentar armar pedido para categoría que no le pertenece falla validación
        $this->withClubToken($coachToken)->postJson('/api/orders', [
            'type' => 'apparel',
            'category_id' => $cat2->id,
            'apparel_order_items' => [
                [
                    'player_id' => $player2->id,
                    'apparel_item_id' => $apparel->id,
                    'size' => 'M',
                    'quantity' => 1,
                ],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_id');

        // 3. Armar pedido para su categoría asignada tiene éxito
        $this->withClubToken($coachToken)->postJson('/api/orders', [
            'type' => 'apparel',
            'category_id' => $cat1->id,
            'apparel_order_items' => [
                [
                    'player_id' => $player1->id,
                    'apparel_item_id' => $apparel->id,
                    'size' => 'M',
                    'quantity' => 1,
                ],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.category_id', $cat1->id);
    }

    public function test_decision_d13_order_number_sequence_per_club_and_year(): void
    {
        $year = (int) date('Y');

        $this->inTenant($this->tenantA(), function () use ($year) {
            $num1 = Order::generateOrderNumber();
            $o1 = Order::factory()->create(['order_number' => $num1]);

            $num2 = Order::generateOrderNumber();
            $o2 = Order::factory()->create(['order_number' => $num2]);

            $this->assertSame(sprintf('%04d-0001', $year), $num1);
            $this->assertSame(sprintf('%04d-0002', $year), $num2);
        });

        // En el club B, la secuencia arranca independiente en 0001
        $this->inTenant($this->tenantB(), function () use ($year) {
            $numB1 = Order::generateOrderNumber();
            $this->assertSame(sprintf('%04d-0001', $year), $numB1);
        });
    }

    public function test_tenant_isolation_orders_and_items_of_another_club_return_404(): void
    {
        $orderB = $this->inTenant($this->tenantB(), fn () => Order::factory()->create());

        $this->withClubToken($this->clubToken($this->tenantA()))
            ->getJson("/api/orders/{$orderB->id}")
            ->assertNotFound();
    }
}
