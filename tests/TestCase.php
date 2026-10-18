<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Lo usa Concerns\InteractsWithTenants: las bases de club de prueba se migran una vez por proceso.
     * Va acá y no en el trait porque una propiedad estática de un trait es una por cada clase que lo usa.
     */
    protected static bool $tenantDatabasesMigrated = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Guard: los tests nunca tienen que tocar la base de desarrollo. Si alguien corrió
        // "php artisan config:cache", phpunit.xml se ignora y los tests irían contra sportiva_platform.
        $database = config('database.connections.'.config('database.default').'.database');
        if ($database !== 'sportiva_testing') {
            throw new RuntimeException(
                "Los tests tienen que usar la base sportiva_testing, no [{$database}]. "
                .'Si corriste config:cache, ejecutá: php artisan config:clear'
            );
        }
    }
}
