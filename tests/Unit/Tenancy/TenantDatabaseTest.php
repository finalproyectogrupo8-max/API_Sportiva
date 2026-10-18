<?php

namespace Tests\Unit\Tenancy;

use App\Models\Tenant;
use App\Tenancy\InvalidTenantDatabaseName;
use App\Tenancy\TenantDatabase;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;
use RuntimeException;
use Tests\TestCase;

class TenantDatabaseTest extends TestCase
{
    use RefreshDatabase;

    private TenantDatabase $tenantDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantDatabase = app(TenantDatabase::class);
    }

    private function tenant(string $databaseName): Tenant
    {
        return (new Tenant)->forceFill(['id' => 99, 'database_name' => $databaseName]);
    }

    private function tenantConnectionDatabase(): ?string
    {
        return config('database.connections.tenant.database');
    }

    public function test_rejects_database_names_outside_the_convention(): void
    {
        foreach (['otra_base', 'club_', 'club_River', 'club_a-b', 'club_a`; DROP DATABASE x', 'club_'.str_repeat('a', 60)] as $name) {
            try {
                $this->tenantDatabase->connect($this->tenant($name));
                $this->fail("Aceptó el nombre [{$name}].");
            } catch (InvalidTenantDatabaseName $e) {
                $this->assertSame(99, $e->tenantId);
                $this->assertStringNotContainsString($name, $e->getMessage());
                $this->assertNull($this->tenantConnectionDatabase());
            }
        }
    }

    public function test_in_testing_only_club_test_databases_are_allowed(): void
    {
        foreach (['connect', 'create', 'drop'] as $method) {
            try {
                $this->tenantDatabase->{$method}($this->tenant('club_river'));
                $this->fail("{$method} aceptó club_river en testing.");
            } catch (LogicException $e) {
                $this->assertStringContainsString('club_test_', $e->getMessage());
                $this->assertNull($this->tenantConnectionDatabase());
            }
        }
    }

    public function test_connect_and_disconnect_point_the_tenant_connection(): void
    {
        $this->tenantDatabase->connect($this->tenant('club_test_a'));
        $this->assertSame('club_test_a', $this->tenantConnectionDatabase());

        $this->tenantDatabase->disconnect();
        $this->assertNull($this->tenantConnectionDatabase());
    }

    public function test_run_disconnects_even_when_the_callback_throws(): void
    {
        try {
            $this->tenantDatabase->run($this->tenant('club_test_a'), function () {
                $this->assertSame('club_test_a', $this->tenantConnectionDatabase());

                throw new RuntimeException('falla el callback');
            });
            $this->fail('run no propagó la excepción.');
        } catch (RuntimeException $e) {
            $this->assertSame('falla el callback', $e->getMessage());
        }

        $this->assertNull($this->tenantConnectionDatabase());
    }

    public function test_a_query_without_tenant_context_fails(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('No database selected');

        DB::connection('tenant')->table('sports')->count();
    }

    public function test_create_and_migrate_are_idempotent_and_leave_the_central_database_alone(): void
    {
        $tenant = $this->tenant('club_test_ops');
        $centralMigrations = DB::table('migrations')->count();

        try {
            $this->tenantDatabase->create($tenant);
            $this->tenantDatabase->migrate($tenant);
            $this->tenantDatabase->run($tenant, fn () => DB::connection('tenant')->table('sports')->insert(['name' => 'Fútbol']));

            $this->tenantDatabase->create($tenant);
            $this->tenantDatabase->migrate($tenant);

            $this->tenantDatabase->run($tenant, function () {
                $this->assertTrue(Schema::connection('tenant')->hasTable('sports'));
                $this->assertSame(1, DB::connection('tenant')->table('sports')->count());
            });
            $this->assertSame($centralMigrations, DB::table('migrations')->count());
            $this->assertFalse(Schema::hasTable('sports'));
            $this->assertNull($this->tenantConnectionDatabase());
        } finally {
            $this->tenantDatabase->drop($tenant);
        }

        $this->assertEmpty(DB::select('SELECT 1 FROM information_schema.schemata WHERE schema_name = ?', ['club_test_ops']));
    }
}
