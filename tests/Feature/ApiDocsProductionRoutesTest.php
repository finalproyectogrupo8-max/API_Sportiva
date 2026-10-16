<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * En producción las rutas de documentación no se registran: /api/docs tiene que ser
 * indistinguible de cualquier ruta inexistente (mismo status, cuerpo y headers, también
 * con OPTIONS y POST). Arranca la aplicación con APP_ENV=production.
 */
class ApiDocsProductionRoutesTest extends TestCase
{
    private ?string $originalEnvironment = null;

    protected function setUp(): void
    {
        $this->originalEnvironment = getenv('APP_ENV') ?: null;
        $this->setEnvironmentVariable('production');

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->setEnvironmentVariable($this->originalEnvironment);
    }

    public function test_application_booted_as_production(): void
    {
        $this->assertTrue($this->app->environment('production'));
    }

    public function test_documentation_routes_are_not_registered(): void
    {
        $routes = $this->app['router']->getRoutes();

        $this->assertNull($routes->getByName('api-docs.ui'));
        $this->assertNull($routes->getByName('api-docs.json'));
    }

    public function test_docs_answer_exactly_like_a_missing_route(): void
    {
        // Como en producción: con debug, el 404 incluye trace y rutas de archivos.
        config(['app.debug' => false]);

        foreach (['GET', 'OPTIONS', 'POST'] as $method) {
            $docs = $this->call($method, '/api/docs');
            $missing = $this->call($method, '/api/no-existe');

            $this->assertSame(404, $docs->getStatusCode(), "{$method} /api/docs");
            $this->assertSame($missing->getStatusCode(), $docs->getStatusCode(), $method);
            $this->assertSame(
                str_replace('api/no-existe', 'api/docs', (string) $missing->getContent()),
                (string) $docs->getContent(),
                "{$method}: el cuerpo tiene que ser el de una ruta inexistente",
            );
            $this->assertFalse($docs->headers->has('X-RateLimit-Limit'), "{$method}: sin headers del throttle");
            $this->assertFalse($docs->headers->has('Allow'), "{$method}: sin Allow");
        }
    }

    private function setEnvironmentVariable(?string $value): void
    {
        if ($value === null) {
            putenv('APP_ENV');
            unset($_ENV['APP_ENV'], $_SERVER['APP_ENV']);

            return;
        }

        putenv("APP_ENV={$value}");
        $_ENV['APP_ENV'] = $value;
        $_SERVER['APP_ENV'] = $value;
    }
}
