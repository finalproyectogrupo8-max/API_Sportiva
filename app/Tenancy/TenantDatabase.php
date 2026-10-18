<?php

namespace App\Tenancy;

use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * La única clase que toca la conexión "tenant" (ADR 0002). La apunta a la base de un club
 * reconfigurándola y con DB::purge(), nunca con "USE <base>" (el proxy de Laravel Cloud lo rechaza).
 */
class TenantDatabase
{
    public const CONNECTION = 'tenant';

    public const MIGRATIONS_PATH = 'database/migrations/tenant';

    /** club_<slug>: minúsculas, dígitos y "_", 64 caracteres como máximo (límite de MySQL). */
    private const NAME_PATTERN = '/^club_[a-z0-9_]{1,59}$/';

    private const TEST_PREFIX = 'club_test_';

    public function connect(Tenant $tenant): void
    {
        $this->point($this->databaseName($tenant));
    }

    public function disconnect(): void
    {
        $this->point(null);
    }

    /**
     * Ejecuta el callback con la conexión apuntando a la base del club, y la deja sin base al
     * terminar, también si el callback lanza una excepción. Es lo que usan los jobs y los tests.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public function run(Tenant $tenant, callable $callback): mixed
    {
        $this->connect($tenant);

        try {
            return $callback();
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Idempotente. Corre sobre la conexión "tenant" (no hace falta que apunte a una base) y no sobre
     * la central: CREATE DATABASE hace un commit implícito, que cerraría una transacción abierta
     * en la central (por ejemplo, la de RefreshDatabase en los tests).
     */
    public function create(Tenant $tenant): void
    {
        $name = $this->databaseName($tenant);
        $charset = config('database.connections.'.self::CONNECTION.'.charset');
        $collation = config('database.connections.'.self::CONNECTION.'.collation');

        DB::connection(self::CONNECTION)
            ->statement("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET {$charset} COLLATE {$collation}");
    }

    /** Aplica solo las migraciones de club, con su propia tabla "migrations" en la base del club. */
    public function migrate(Tenant $tenant, bool $fresh = false): void
    {
        $this->run($tenant, fn () => Artisan::call($fresh ? 'migrate:fresh' : 'migrate', [
            '--database' => self::CONNECTION,
            '--path' => self::MIGRATIONS_PATH,
            '--force' => true,
        ]));
    }

    /**
     * Por ahora solo borra bases de test: el borrado de un club real es una decisión abierta
     * (docs/arquitectura-multi-tenant.md).
     */
    public function drop(Tenant $tenant): void
    {
        $name = $this->databaseName($tenant);

        if (! str_starts_with($name, self::TEST_PREFIX)) {
            throw new LogicException('TenantDatabase::drop solo borra bases '.self::TEST_PREFIX.'*.');
        }

        if (config('database.connections.'.self::CONNECTION.'.database') === $name) {
            $this->disconnect();
        }

        // Por la misma razón que create, sobre la conexión "tenant".
        DB::connection(self::CONNECTION)->statement("DROP DATABASE IF EXISTS `{$name}`");
    }

    /** El nombre validado: nunca se interpola en SQL ni se usa para conectar uno sin validar. */
    private function databaseName(Tenant $tenant): string
    {
        $name = (string) $tenant->database_name;

        if (! preg_match(self::NAME_PATTERN, $name)) {
            throw new InvalidTenantDatabaseName($tenant->id);
        }

        if (app()->environment('testing') && ! str_starts_with($name, self::TEST_PREFIX)) {
            throw new LogicException(
                'En testing solo se usan bases '.self::TEST_PREFIX."*, no [{$name}]: "
                .'creá los clubes de prueba con Tests\Concerns\InteractsWithTenants o con un slug test_*.'
            );
        }

        return $name;
    }

    private function point(?string $database): void
    {
        config(['database.connections.'.self::CONNECTION.'.database' => $database]);
        DB::purge(self::CONNECTION);
    }
}
