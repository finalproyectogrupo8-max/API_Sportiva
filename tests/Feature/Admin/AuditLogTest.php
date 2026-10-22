<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use LogicException;
use RuntimeException;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function asAdmin(): User
    {
        $admin = User::factory()->platformAdmin()->create();
        Sanctum::actingAs($admin, ['*']);

        return $admin;
    }

    public function test_store_records_tenant_created_with_actor_ip_and_changes(): void
    {
        $admin = $this->asAdmin();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
            ->postJson('/api/admin/tenants', ['name' => 'Club River', 'slug' => 'river'])
            ->assertCreated();

        $log = AuditLog::query()->sole();

        $this->assertSame($admin->id, $log->actor_id);
        $this->assertSame('203.0.113.7', $log->ip);
        $this->assertSame('tenant.created', $log->action);
        $this->assertSame('tenant', $log->subject_type);
        $this->assertSame(Tenant::query()->sole()->id, $log->subject_id);
        $this->assertSame('club_river', $log->changes['database_name']['to']);
        $this->assertNotNull($log->created_at);
    }

    public function test_update_records_only_changed_fields_with_from_and_to_values(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Original', 'status' => 'active']);
        $this->asAdmin();

        $this->patchJson("/api/admin/tenants/{$tenant->id}", ['name' => 'Nuevo'])->assertOk();

        $changes = AuditLog::query()->sole()->changes;

        $this->assertSame(['name'], array_keys($changes));
        $this->assertSame('Original', $changes['name']['from']);
        $this->assertSame('Nuevo', $changes['name']['to']);
    }

    public function test_update_with_no_real_changes_does_not_record_a_log(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Club River']);
        $this->asAdmin();

        $this->patchJson("/api/admin/tenants/{$tenant->id}", ['name' => 'Club River'])->assertOk();

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_destroy_records_tenant_deleted(): void
    {
        $tenant = Tenant::factory()->create();
        $this->asAdmin();

        $this->deleteJson("/api/admin/tenants/{$tenant->id}")->assertNoContent();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'tenant.deleted',
            'subject_type' => 'tenant',
            'subject_id' => $tenant->id,
        ]);
    }

    public function test_validation_failure_does_not_record_a_log(): void
    {
        $this->asAdmin();

        $this->postJson('/api/admin/tenants', ['slug' => 'river'])
            ->assertUnprocessable();

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_audit_failure_rolls_back_tenant_creation(): void
    {
        $this->asAdmin();
        $this->mock(AuditLogger::class)
            ->shouldReceive('recordCreated')
            ->once()
            ->andThrow(new RuntimeException('Audit storage unavailable.'));

        $this->postJson('/api/admin/tenants', ['name' => 'Club River', 'slug' => 'river'])
            ->assertInternalServerError();

        $this->assertDatabaseCount('tenants', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_audit_log_cannot_be_updated(): void
    {
        $log = AuditLog::query()->create([
            'action' => 'tenant.created',
            'subject_type' => 'tenant',
            'subject_id' => 1,
        ]);

        $this->expectException(LogicException::class);
        $log->update(['action' => 'tenant.updated']);
    }

    public function test_audit_log_cannot_be_deleted(): void
    {
        $log = AuditLog::query()->create([
            'action' => 'tenant.created',
            'subject_type' => 'tenant',
            'subject_id' => 1,
        ]);

        $this->expectException(LogicException::class);
        $log->delete();
    }

    public function test_password_change_is_logged_without_the_password_or_hash(): void
    {
        $this->asAdmin();
        $user = User::factory()->create();
        $newPassword = 'new-secret-password';
        $user->forceFill(['password' => $newPassword]);

        app(AuditLogger::class)->recordUpdated($user);

        $changes = AuditLog::query()->sole()->changes;

        $this->assertSame(['changed' => true], $changes['password']);
        $encodedChanges = (string) json_encode($changes);
        $this->assertStringNotContainsString($newPassword, $encodedChanges);
        $this->assertStringNotContainsString($user->getAttribute('password'), $encodedChanges);
    }
}
