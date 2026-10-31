<?php

namespace Tests\Feature\Club;

use App\Models\Club\ClubUser;
use App\Models\Club\Facility;
use App\Models\Club\FacilityComponent;
use App\Models\Club\MaintenanceRecord;
use App\Models\Club\RepairRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** Tests del flujo de reparaciones y registros de mantenimiento (TIZ-19). */
class MaintenanceApiTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    private function token(string $role = 'admin'): string
    {
        return $this->clubToken($this->tenantA(), roles: [$role]);
    }

    public function test_full_maintenance_workflow_report_assign_resolve_component_restored_to_ok(): void
    {
        // Criterio de aceptación: Se reporta un problema en el aro de la cancha 1, se asigna,
        // se resuelve y el componente vuelve a "ok". Todo en una transacción con historial.
        [$facility, $component, $technicianUser] = $this->inTenant($this->tenantA(), function () {
            $fac = Facility::factory()->create(['name' => 'Cancha 1']);
            $comp = FacilityComponent::factory()->create([
                'facility_id' => $fac->id,
                'name' => 'Aro de básquet',
                'condition_status' => 'out_of_service',
            ]);

            $tech = User::factory()->create();
            $clubTech = ClubUser::create([
                'global_user_id' => $tech->id,
                'display_name' => 'Técnico de Mantenimiento',
                'status' => 'active',
            ]);

            return [$fac, $comp, $clubTech];
        });

        // 1. Un profe reporta el problema en el aro de la cancha 1
        $coachToken = $this->token('coach');
        $reportRes = $this->withClubToken($coachToken)->postJson('/api/repair-requests', [
            'facility_id' => $facility->id,
            'component_id' => $component->id,
            'title' => 'Aro roto tras partido',
            'description' => 'El aro cedió en la fijación al tablero y no se puede usar.',
            'priority' => 'urgent',
        ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Aro roto tras partido')
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.priority', 'urgent')
            ->assertJsonPath('data.facility_id', $facility->id)
            ->assertJsonPath('data.component_id', $component->id);

        $repairRequestId = $reportRes->json('data.id');

        // 2. Encargado de mantenimiento asigna la reparación al técnico
        $maintenanceToken = $this->token('maintenance_manager');
        $this->withClubToken($maintenanceToken)->postJson("/api/repair-requests/{$repairRequestId}/assign", [
            'assigned_to' => $technicianUser->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'assigned')
            ->assertJsonPath('data.assigned_to', $technicianUser->id);

        $this->assertSame('assigned', $this->inTenant($this->tenantA(), fn () => RepairRequest::find($repairRequestId)->status));

        // 3. Se resuelve la reparación
        $this->withClubToken($maintenanceToken)->postJson("/api/repair-requests/{$repairRequestId}/resolve", [
            'description' => 'Se soldó y reforzó la base del aro y se ajustó la tornillería.',
            'cost' => 12500.50,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'resolved')
            ->assertJsonPath('data.maintenance_record.description', 'Se soldó y reforzó la base del aro y se ajustó la tornillería.');

        // Verificaciones finales en base de datos:
        $this->inTenant($this->tenantA(), function () use ($repairRequestId, $component, $facility) {
            $repair = RepairRequest::find($repairRequestId);
            $this->assertSame('resolved', $repair->status);
            $this->assertNotNull($repair->resolved_at);

            // El componente volvió a "ok"
            $comp = FacilityComponent::find($component->id);
            $this->assertSame('ok', $comp->condition_status);
            $this->assertNotNull($comp->last_inspected_at);

            // Existe el registro de mantenimiento vinculado
            $record = MaintenanceRecord::where('repair_request_id', $repairRequestId)->first();
            $this->assertNotNull($record);
            $this->assertSame($facility->id, $record->facility_id);
            $this->assertEquals(12500.50, (float) $record->cost);
        });
    }

    public function test_resolve_unassigned_request_fails_with_409(): void
    {
        $repairRequest = $this->inTenant($this->tenantA(), fn () => RepairRequest::factory()->create([
            'status' => 'open',
            'assigned_to' => null,
        ]));

        $this->withClubToken($this->token('maintenance_manager'))
            ->postJson("/api/repair-requests/{$repairRequest->id}/resolve", [
                'description' => 'Intento de resolución directa',
            ])
            ->assertConflict()
            ->assertJsonPath('code', 'repair_not_assigned');
    }

    public function test_resolve_already_resolved_request_fails_with_409(): void
    {
        $repairRequest = $this->inTenant($this->tenantA(), fn () => RepairRequest::factory()->create([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]));

        $this->withClubToken($this->token('maintenance_manager'))
            ->postJson("/api/repair-requests/{$repairRequest->id}/resolve", [
                'description' => 'Segundo intento',
            ])
            ->assertConflict()
            ->assertJsonPath('code', 'repair_already_resolved');
    }

    public function test_assign_already_resolved_request_fails_with_409(): void
    {
        [$repairRequest, $user] = $this->inTenant($this->tenantA(), function () {
            $repair = RepairRequest::factory()->create([
                'status' => 'resolved',
                'resolved_at' => now(),
            ]);
            $u = User::factory()->create();
            $cu = ClubUser::create(['global_user_id' => $u->id, 'display_name' => 'Tech', 'status' => 'active']);

            return [$repair, $cu];
        });

        $this->withClubToken($this->token('maintenance_manager'))
            ->postJson("/api/repair-requests/{$repairRequest->id}/assign", [
                'assigned_to' => $user->id,
            ])
            ->assertConflict()
            ->assertJsonPath('code', 'repair_already_resolved');
    }

    public function test_permissions_assign_or_resolve_without_permission_returns_403(): void
    {
        $this->getJson('/api/repair-requests')->assertUnauthorized();

        $repairRequest = $this->inTenant($this->tenantA(), fn () => RepairRequest::factory()->create([
            'status' => 'open',
        ]));

        // El profe no tiene repairs.assign ni repairs.update
        $coachToken = $this->token('coach');
        $this->withClubToken($coachToken)
            ->postJson("/api/repair-requests/{$repairRequest->id}/assign", ['assigned_to' => 1])
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden');

        $this->withClubToken($coachToken)
            ->postJson("/api/repair-requests/{$repairRequest->id}/resolve", ['description' => 'Test'])
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden');
    }

    public function test_component_mismatch_with_facility_fails_validation(): void
    {
        [$facility1, $facility2, $componentOfFacility2] = $this->inTenant($this->tenantA(), function () {
            $f1 = Facility::factory()->create();
            $f2 = Facility::factory()->create();
            $c2 = FacilityComponent::factory()->create(['facility_id' => $f2->id]);

            return [$f1, $f2, $c2];
        });

        $this->withClubToken($this->token('maintenance_manager'))->postJson('/api/repair-requests', [
            'facility_id' => $facility1->id,
            'component_id' => $componentOfFacility2->id,
            'title' => 'Falla en componente ajeno',
            'description' => 'Detalle',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('component_id');
    }

    public function test_maintenance_records_crud_and_filters(): void
    {
        [$facility, $record] = $this->inTenant($this->tenantA(), function () {
            $fac = Facility::factory()->create(['name' => 'Cancha 1']);
            $rec = MaintenanceRecord::factory()->create([
                'facility_id' => $fac->id,
                'description' => 'Pintura de líneas reglamentarias',
                'cost' => 8000,
            ]);

            return [$fac, $rec];
        });

        $token = $this->token('maintenance_manager');

        // Listar
        $this->withClubToken($token)->getJson('/api/maintenance-records')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'Pintura de líneas reglamentarias');

        // Filtrar por instalación
        $this->withClubToken($token)->getJson("/api/maintenance-records?facility_id={$facility->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        // Crear mantenimiento preventivo (sin reparación)
        $this->withClubToken($token)->postJson('/api/maintenance-records', [
            'facility_id' => $facility->id,
            'description' => 'Engrase y revisión periódica de bisagras',
            'cost' => 1500,
        ])
            ->assertCreated()
            ->assertJsonPath('data.description', 'Engrase y revisión periódica de bisagras');

        $this->assertSame(2, $this->inTenant($this->tenantA(), fn () => MaintenanceRecord::count()));
    }

    public function test_tenant_isolation_on_repairs_and_maintenance(): void
    {
        [$facilityB, $repairB, $recordB] = $this->inTenant($this->tenantB(), function () {
            $fac = Facility::factory()->create();
            $rep = RepairRequest::factory()->create(['facility_id' => $fac->id]);
            $rec = MaintenanceRecord::factory()->create(['facility_id' => $fac->id, 'repair_request_id' => $rep->id]);

            return [$fac, $rep, $rec];
        });

        $tokenA = $this->token('maintenance_manager');

        // Club A no puede ver ni modificar datos del Club B
        $this->withClubToken($tokenA)->getJson("/api/repair-requests/{$repairB->id}")->assertNotFound();
        $this->withClubToken($tokenA)->postJson("/api/repair-requests/{$repairB->id}/assign", ['assigned_to' => 1])->assertNotFound();
        $this->withClubToken($tokenA)->postJson("/api/repair-requests/{$repairB->id}/resolve", ['description' => 'X'])->assertNotFound();
        $this->withClubToken($tokenA)->getJson("/api/maintenance-records/{$recordB->id}")->assertNotFound();
    }
}
