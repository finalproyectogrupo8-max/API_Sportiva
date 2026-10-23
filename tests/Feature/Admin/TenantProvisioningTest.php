<?php

namespace Tests\Feature\Admin;

use App\Enums\MembershipRole;
use App\Enums\TenantStatus;
use App\Jobs\ProvisionTenant;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Tenancy\TenantDatabase;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class TenantProvisioningTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, Tenant> */
    private array $tenantsToClean = [];

    protected function tearDown(): void
    {
        $tenantDatabase = app(TenantDatabase::class);

        foreach ($this->tenantsToClean as $tenant) {
            try {
                $tenantDatabase->drop($tenant);
            } catch (\Throwable) {
                // Base ya borrada o no creada
            }
        }

        parent::tearDown();
    }

    private function asAdmin(): User
    {
        $admin = User::factory()->platformAdmin()->create();
        Sanctum::actingAs($admin, ['*']);

        return $admin;
    }

    private function trackTenant(Tenant $tenant): Tenant
    {
        $this->tenantsToClean[] = $tenant;

        return $tenant;
    }

    private function skipIfNoMySqlPermissions(Tenant $tenant): void
    {
        try {
            app(TenantDatabase::class)->create($tenant);
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) === 1044) {
                $this->markTestSkipped('El usuario de MySQL no tiene permisos para crear bases club_*. Error 1044.');
            }

            throw $e;
        }
    }

    public function test_store_provisions_database_with_all_tables_on_real_mysql(): void
    {
        $this->asAdmin();

        $probe = Tenant::factory()->make(['slug' => 'test_probe', 'database_name' => 'club_test_probe']);
        $this->trackTenant($probe);
        $this->skipIfNoMySqlPermissions($probe);

        $response = $this->postJson('/api/admin/tenants', [
            'name' => 'Club Test Provisión',
            'slug' => 'test_prov_alpha',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.slug', 'test_prov_alpha')
            ->assertJsonPath('data.database_name', 'club_test_prov_alpha')
            ->assertJsonPath('data.status', 'active');

        $tenant = Tenant::where('slug', 'test_prov_alpha')->firstOrFail();
        $this->trackTenant($tenant);

        $this->assertSame(TenantStatus::Active->value, $tenant->status);

        // Verificar que la base física existe y tiene exactamente las 32 tablas de la carpeta tenant/
        $tenantDatabase = app(TenantDatabase::class);
        $tables = $tenantDatabase->run($tenant, function () {
            $connection = DB::connection(TenantDatabase::CONNECTION);

            return $connection->table('information_schema.tables')
                ->where('table_schema', $connection->getDatabaseName())
                ->where('table_type', 'BASE TABLE')
                ->where('table_name', '!=', 'migrations')
                ->pluck('table_name as name')
                ->all();
        });

        // Contar cuántas migraciones crean tablas en database/migrations/tenant (excluyendo add_foreign_key)
        $migrationFiles = glob(base_path('database/migrations/tenant/*.php')) ?: [];
        $expectedTableMigrations = count(array_filter(
            $migrationFiles,
            fn ($file) => ! str_contains(basename($file), 'add_club_user_foreign_key')
        ));

        $this->assertSame($expectedTableMigrations, count($tables));
        $this->assertContains('sports', $tables);
        $this->assertContains('players', $tables);
        $this->assertContains('categories', $tables);
    }

    public function test_store_provisions_active_status_and_dispatches_job(): void
    {
        $this->asAdmin();

        $this->partialMock(TenantDatabase::class, function ($mock) {
            $mock->shouldReceive('create')->once()->andReturnNull();
            $mock->shouldReceive('migrate')->once()->andReturnNull();
        });

        $response = $this->postJson('/api/admin/tenants', [
            'name' => 'Club Test Mock',
            'slug' => 'test_prov_mock',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.slug', 'test_prov_mock')
            ->assertJsonPath('data.database_name', 'club_test_prov_mock')
            ->assertJsonPath('data.status', 'active');

        $tenant = Tenant::where('slug', 'test_prov_mock')->firstOrFail();
        $this->trackTenant($tenant);

        $this->assertSame(TenantStatus::Active->value, $tenant->status);
    }

    public function test_store_handles_simulated_provisioning_failure_without_500(): void
    {
        $this->asAdmin();

        Log::shouldReceive('error')
            ->atLeast()->once()
            ->withArgs(function (string $message, array $context) {
                return $context['slug'] === 'test_prov_fail'
                    && ! str_contains($message, 'super-secret-password-123')
                    && str_contains($message, 'password=******');
            });
        Log::shouldReceive('info')->zeroOrMoreTimes();

        $this->partialMock(TenantDatabase::class, function ($mock) {
            $mock->shouldReceive('create')
                ->andThrow(new RuntimeException('SQL error connection: mysql://user:super-secret-password-123@host:3306 with password=super-secret-password-123'));
        });

        $response = $this->postJson('/api/admin/tenants', [
            'name' => 'Club Test Falla',
            'slug' => 'test_prov_fail',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'failed');

        $tenant = Tenant::where('slug', 'test_prov_fail')->firstOrFail();
        $this->trackTenant($tenant);

        $this->assertSame(TenantStatus::Failed->value, $tenant->status);
    }

    public function test_provision_tenant_job_is_idempotent(): void
    {
        $tenant = Tenant::factory()->create([
            'slug' => 'test_prov_idem',
            'name' => 'Club Idempotente',
            'database_name' => 'club_test_prov_idem',
            'status' => 'provisioning',
        ]);
        $this->trackTenant($tenant);

        $mockDb = $this->partialMock(TenantDatabase::class, function ($mock) {
            $mock->shouldReceive('create')->once()->andReturnNull();
            $mock->shouldReceive('migrate')->once()->andReturnNull();
        });

        // Primera ejecución
        (new ProvisionTenant($tenant->id))->handle($mockDb);
        $this->assertSame('active', $tenant->fresh()->status);

        // Segunda ejecución sobre club ya activo: no debe volver a llamar a create ni migrate
        (new ProvisionTenant($tenant->id))->handle($mockDb);
        $this->assertSame('active', $tenant->fresh()->status);
    }

    public function test_retry_endpoint_provisions_failed_tenant_and_audits(): void
    {
        $admin = $this->asAdmin();

        $this->partialMock(TenantDatabase::class, function ($mock) {
            $mock->shouldReceive('create')->once()->andReturnNull();
            $mock->shouldReceive('migrate')->once()->andReturnNull();
        });

        $tenant = Tenant::factory()->create([
            'slug' => 'test_prov_retry',
            'name' => 'Club Reintento',
            'database_name' => 'club_test_prov_retry',
            'status' => TenantStatus::Failed->value,
        ]);
        $this->trackTenant($tenant);

        $response = $this->postJson("/api/admin/tenants/{$tenant->id}/retry");

        $response->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->assertSame(TenantStatus::Active->value, $tenant->fresh()->status);

        // Verificar que el retry quedó auditado por el actor
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'tenant.updated',
            'subject_id' => $tenant->id,
        ]);
    }

    public function test_retry_on_non_failed_tenant_returns_409_conflict(): void
    {
        $this->asAdmin();

        $tenant = Tenant::factory()->create([
            'slug' => 'test_prov_active',
            'status' => TenantStatus::Active->value,
        ]);
        $this->trackTenant($tenant);

        $this->postJson("/api/admin/tenants/{$tenant->id}/retry")
            ->assertStatus(409)
            ->assertJsonPath('code', 'tenant_not_failed');
    }

    public function test_retry_authorization_denies_unauthenticated_and_non_admin(): void
    {
        $tenant = Tenant::factory()->create([
            'slug' => 'test_prov_auth',
            'status' => TenantStatus::Failed->value,
        ]);
        $this->trackTenant($tenant);

        // Sin token: 401
        $this->postJson("/api/admin/tenants/{$tenant->id}/retry")
            ->assertUnauthorized();

        // Con usuario no administrador: 403 forbidden
        Sanctum::actingAs(User::factory()->create(['is_platform_admin' => false]), ['*']);

        $this->postJson("/api/admin/tenants/{$tenant->id}/retry")
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden');
    }

    public function test_member_can_select_tenant_on_newly_provisioned_club(): void
    {
        $this->asAdmin();

        $this->partialMock(TenantDatabase::class, function ($mock) {
            $mock->shouldReceive('create')->once()->andReturnNull();
            $mock->shouldReceive('migrate')->once()->andReturnNull();
        });

        // 1. Alta del club
        $this->postJson('/api/admin/tenants', [
            'name' => 'Club Atletico Seleccion',
            'slug' => 'test_prov_select',
        ])->assertCreated();

        $tenant = Tenant::where('slug', 'test_prov_select')->firstOrFail();
        $this->trackTenant($tenant);

        // 2. Crear usuario y membresía en ese club
        $user = User::factory()->create(['password' => bcrypt('secret-password-12')]);
        TenantMembership::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => MembershipRole::Admin->value,
            'is_active' => true,
        ]);

        // 3. Login para obtener token pre-tenant (reset de guards para limpiar el usuario admin)
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();

        $loginRes = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret-password-12',
        ])->assertOk();

        $preToken = $loginRes->json('token');

        // 4. Select tenant
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();

        $selectRes = $this->withToken($preToken)
            ->postJson('/api/auth/select-tenant', [
                'tenant' => 'test_prov_select',
            ])->assertOk()
            ->assertJsonPath('tenant.slug', 'test_prov_select');

        $clubToken = $selectRes->json('token');

        // 5. Consulta a /api/me con el token del club (reset de guards para tomar el nuevo token)
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();

        $this->withToken($clubToken)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('tenant.slug', 'test_prov_select')
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_tenants_create_command_provisions_active_club(): void
    {
        $this->partialMock(TenantDatabase::class, function ($mock) {
            $mock->shouldReceive('create')->once()->andReturnNull();
            $mock->shouldReceive('migrate')->once()->andReturnNull();
        });

        $this->artisan('tenants:create', [
            'slug' => 'test_prov_cmd',
            'name' => 'Club Command',
        ])->assertSuccessful();

        $tenant = Tenant::where('slug', 'test_prov_cmd')->firstOrFail();
        $this->trackTenant($tenant);

        $this->assertSame(TenantStatus::Active->value, $tenant->status);
    }

    public function test_tenants_create_command_validates_invalid_slug(): void
    {
        $this->artisan('tenants:create', [
            'slug' => 'Slug-Invalido!',
            'name' => 'Club Invalido',
        ])->assertFailed();

        $this->assertDatabaseMissing('tenants', ['name' => 'Club Invalido']);
    }

    public function test_tenants_migrate_command_runs_without_errors(): void
    {
        $tenant = Tenant::factory()->create([
            'slug' => 'test_prov_mig',
            'database_name' => 'club_test_prov_mig',
            'status' => 'active',
        ]);
        $this->trackTenant($tenant);

        $this->partialMock(TenantDatabase::class, function ($mock) {
            $mock->shouldReceive('migrate')->once()->andReturnNull();
        });

        $this->artisan('tenants:migrate', ['--tenant' => 'test_prov_mig'])
            ->assertSuccessful();
    }

    public function test_tenants_migrate_command_is_cancelled_in_production_without_force(): void
    {
        $tenant = Tenant::factory()->create([
            'slug' => 'test_prov_prod',
            'database_name' => 'club_test_prov_prod',
            'status' => 'active',
        ]);
        $this->trackTenant($tenant);

        $this->partialMock(TenantDatabase::class, function ($mock) {
            $mock->shouldNotReceive('migrate');
        });

        $this->app['env'] = 'production';

        // Sin --force, la confirmación responde "no" por defecto y no se toca ninguna base.
        $this->artisan('tenants:migrate', ['--fresh' => true, '--no-interaction' => true])
            ->assertFailed();
    }

    public function test_tenants_migrate_command_runs_in_production_with_force(): void
    {
        $tenant = Tenant::factory()->create([
            'slug' => 'test_prov_force',
            'database_name' => 'club_test_prov_force',
            'status' => 'active',
        ]);
        $this->trackTenant($tenant);

        $this->partialMock(TenantDatabase::class, function ($mock) {
            $mock->shouldReceive('migrate')->once()->andReturnNull();
        });

        $this->app['env'] = 'production';

        $this->artisan('tenants:migrate', ['--tenant' => 'test_prov_force', '--force' => true])
            ->assertSuccessful();
    }
}
