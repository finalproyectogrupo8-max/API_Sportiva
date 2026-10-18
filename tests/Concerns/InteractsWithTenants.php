<?php

namespace Tests\Concerns;

use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Tenancy\TenantDatabase;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\Fixtures\TestItem;

/**
 * Dos clubes de prueba (club_test_a y club_test_b) con sus bases migradas. Se usa junto con
 * RefreshDatabase: los clubes se crean en la base central dentro de su transacción.
 *
 * Las bases de club no van en una transacción (DB::purge cierra la conexión y la perdería):
 * se migran una vez por proceso y se truncan antes de cada test.
 */
trait InteractsWithTenants
{
    private Tenant $tenantA;

    private Tenant $tenantB;

    protected function setUpInteractsWithTenants(): void
    {
        $this->tenantA = $this->createTestTenant('test_a');
        $this->tenantB = $this->createTestTenant('test_b');

        if (! static::$tenantDatabasesMigrated) {
            foreach ([$this->tenantA, $this->tenantB] as $tenant) {
                $this->createTenantDatabase($tenant);
                app(TenantDatabase::class)->migrate($tenant, fresh: true);
                $this->inTenant($tenant, fn () => Artisan::call('migrate', [
                    '--database' => TenantDatabase::CONNECTION,
                    '--path' => 'tests/Fixtures/tenant-migrations',
                    '--force' => true,
                ]));
            }

            static::$tenantDatabasesMigrated = true;
        }

        foreach ([$this->tenantA, $this->tenantB] as $tenant) {
            $this->truncateTenantTables($tenant);
        }

        $this->registerTestItemRoutes();
    }

    protected function tenantA(): Tenant
    {
        return $this->tenantA;
    }

    protected function tenantB(): Tenant
    {
        return $this->tenantB;
    }

    /** Un token de club como el que emite select-tenant, con un usuario miembro activo del club. */
    protected function clubToken(Tenant $tenant, ?User $user = null): string
    {
        $user ??= User::factory()->create();

        TenantMembership::firstOrCreate(
            ['user_id' => $user->id, 'tenant_id' => $tenant->id],
            ['role' => 'admin'],
        );

        $token = $user->createToken(
            'tenant:'.$tenant->slug,
            ['tenant:select', 'tenant:operate'],
            now()->addMinutes(config('sanctum.tenant_ttl')),
        );
        $token->accessToken->forceFill(['tenant_id' => $tenant->id])->save();

        return $token->plainTextToken;
    }

    /**
     * Prepara el próximo request con ese token. El guard de Sanctum cachea el usuario entre
     * requests del mismo test: sin el reset, un segundo request usaría el token del primero.
     */
    protected function withClubToken(string $token): static
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();

        return $this->withToken($token);
    }

    /** Para cargar datos o hacer asserts dentro de la base de un club. */
    protected function inTenant(Tenant $tenant, callable $callback): mixed
    {
        return app(TenantDatabase::class)->run($tenant, $callback);
    }

    private function createTestTenant(string $slug): Tenant
    {
        return Tenant::factory()->create([
            'slug' => $slug,
            'name' => 'Club '.$slug,
            'database_name' => 'club_'.$slug,
            'status' => 'active',
        ]);
    }

    private function createTenantDatabase(Tenant $tenant): void
    {
        try {
            app(TenantDatabase::class)->create($tenant);
        } catch (QueryException $e) {
            // 1044: access denied. El volumen de MySQL es anterior al permiso sobre club_*.
            if (($e->errorInfo[1] ?? null) === 1044) {
                throw new RuntimeException(
                    "El usuario de MySQL no puede crear {$tenant->database_name}: a tu volumen le falta el permiso sobre "
                    .'las bases club_*. Ver "Los tests de club fallan por permisos de MySQL" en docs/desarrollo-local.md '
                    .'(hay que recrear el volumen, y eso borra la base local).',
                    previous: $e,
                );
            }

            throw $e;
        }
    }

    private function truncateTenantTables(Tenant $tenant): void
    {
        $this->inTenant($tenant, function () {
            $connection = DB::connection(TenantDatabase::CONNECTION);
            $tables = $connection->table('information_schema.tables')
                ->where('table_schema', $connection->getDatabaseName())
                ->where('table_type', 'BASE TABLE')
                ->where('table_name', '!=', 'migrations')
                ->pluck('table_name as name'); // MySQL 8 devuelve TABLE_NAME en mayúsculas

            $connection->statement('SET FOREIGN_KEY_CHECKS=0');
            foreach ($tables as $table) {
                $connection->table($table)->truncate();
            }
            $connection->statement('SET FOREIGN_KEY_CHECKS=1');
        });
    }

    /** Rutas de club solo para tests: se registran acá, así que nunca existen fuera de la suite. */
    private function registerTestItemRoutes(): void
    {
        Route::middleware(['api', 'auth:sanctum', 'tenant.token'])
            ->prefix('api/test-items')
            ->group(function () {
                Route::get('/', fn () => ['data' => TestItem::orderBy('id')->get()]);
                Route::get('{testItem}', fn (TestItem $testItem) => ['data' => $testItem]);
                Route::patch('{testItem}', function (Request $request, TestItem $testItem) {
                    $testItem->update(['name' => $request->input('name')]);

                    return ['data' => $testItem];
                });
            });

        Route::getRoutes()->refreshNameLookups();
        Route::getRoutes()->refreshActionLookups();
    }
}
