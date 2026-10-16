<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Las rutas reales de documentación: el contrato OpenAPI y la página de Swagger UI.
 * La matriz de acceso por entorno está en ApiDocsAccessTest.
 */
class ApiDocsContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // La documentación solo es accesible en local (o staging con credenciales).
        $this->app['env'] = 'local';
    }

    public function test_serves_an_openapi_3_1_document_with_the_api_routes(): void
    {
        $response = $this->getJson('/api/docs/openapi.json')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex');

        $this->assertStringStartsWith('3.1.', $response->json('openapi'));
        $this->assertArrayHasKey('/health', $response->json('paths'));
    }

    public function test_the_documentation_routes_are_not_part_of_the_contract(): void
    {
        $paths = array_keys($this->getJson('/api/docs/openapi.json')->json('paths'));

        foreach ($paths as $path) {
            $this->assertStringStartsNotWith('/docs', $path);
        }
    }

    public function test_ui_loads_pinned_swagger_ui_with_integrity_and_points_to_the_contract(): void
    {
        $version = config('scramble.renderers.swagger.version');

        $this->get('/api/docs')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=utf-8')
            ->assertSee("swagger-ui-dist@{$version}/swagger-ui-bundle.js", false)
            ->assertSee('integrity="'.config('scramble.renderers.swagger.integrity.js').'"', false)
            ->assertSee('integrity="'.config('scramble.renderers.swagger.integrity.css').'"', false)
            ->assertSee('crossorigin="anonymous"', false)
            ->assertSee('"\/api\/docs\/openapi.json"', false);
    }

    public function test_pinned_version_is_exact_and_hashes_are_sha384(): void
    {
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', config('scramble.renderers.swagger.version'));
        $this->assertStringStartsWith('sha384-', config('scramble.renderers.swagger.integrity.js'));
        $this->assertStringStartsWith('sha384-', config('scramble.renderers.swagger.integrity.css'));
    }

    public function test_real_routes_are_hidden_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->get('/api/docs')->assertNotFound();
        $this->get('/api/docs/openapi.json')->assertNotFound();
    }

    public function test_ui_sends_a_strict_content_security_policy_with_a_nonce(): void
    {
        $response = $this->get('/api/docs')->assertOk();

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString("default-src 'none'", $csp);
        $this->assertStringContainsString("connect-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);

        preg_match("/'nonce-([^']+)'/", $csp, $match);
        $this->assertNotEmpty($match[1] ?? null);
        $response->assertSee('<script nonce="'.$match[1].'">', false);
        $response->assertSee('validatorUrl: null', false);
    }

    public function test_default_scramble_routes_are_not_registered(): void
    {
        $this->get('/docs/api')->assertNotFound();
        $this->get('/docs/api.json')->assertNotFound();
    }
}
