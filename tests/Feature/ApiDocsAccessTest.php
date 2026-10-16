<?php

namespace Tests\Feature;

use App\Http\Middleware\ApiDocsAccess;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Matriz de acceso de la spec api-docs, probada sobre una ruta de test protegida por el
 * mismo middleware. Las rutas reales (/api/docs) se prueban en ApiDocsContractTest.
 */
class ApiDocsAccessTest extends TestCase
{
    private const URI = '/api/_test/docs-guard';

    protected function setUp(): void
    {
        parent::setUp();

        Route::get(self::URI, fn () => response()->json(['ok' => true]))
            ->middleware(['api', ApiDocsAccess::class]);
    }

    public function test_local_is_open_without_credentials(): void
    {
        $this->useEnvironment('local');

        $this->get(self::URI)
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_staging_without_authorization_returns_401_with_basic_challenge(): void
    {
        $this->useEnvironment('staging');
        $this->configureCredentials('docs', 's3cret');

        $this->get(self::URI)
            ->assertUnauthorized()
            ->assertHeader('WWW-Authenticate', 'Basic realm="API Sportiva docs", charset="UTF-8"')
            ->assertJsonStructure(['message']);
    }

    public function test_staging_with_wrong_credentials_returns_401(): void
    {
        $this->useEnvironment('staging');
        $this->configureCredentials('docs', 's3cret');

        $this->withBasicAuth('docs', 'incorrecta')->get(self::URI)->assertUnauthorized();
        $this->withBasicAuth('otro', 's3cret')->get(self::URI)->assertUnauthorized();
    }

    public function test_staging_with_correct_credentials_is_allowed(): void
    {
        $this->useEnvironment('staging');
        $this->configureCredentials('docs', 's3cret');

        $this->withBasicAuth('docs', 's3cret')->get(self::URI)
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex');
    }

    public function test_staging_without_configured_credentials_returns_404(): void
    {
        $this->useEnvironment('staging');
        $this->configureCredentials(null, null);

        $this->get(self::URI)->assertNotFound();
        $this->withBasicAuth('', '')->get(self::URI)->assertNotFound();

        $this->configureCredentials('docs', '');
        $this->withBasicAuth('docs', '')->get(self::URI)->assertNotFound();
    }

    public function test_production_returns_404_even_with_credentials(): void
    {
        $this->useEnvironment('production');
        $this->configureCredentials('docs', 's3cret');

        $this->get(self::URI)->assertNotFound();
        $this->withBasicAuth('docs', 's3cret')->get(self::URI)->assertNotFound();
    }

    public function test_unknown_environments_return_404(): void
    {
        $this->useEnvironment('testing');

        $this->get(self::URI)->assertNotFound();
    }

    public function test_allowed_responses_carry_security_headers(): void
    {
        $this->useEnvironment('local');

        $this->get(self::URI)
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'no-referrer');
    }

    public function test_staging_basic_auth_is_rate_limited(): void
    {
        $this->useEnvironment('staging');
        $this->configureCredentials('docs', 's3cret');

        // Mismo middleware y throttle que las rutas reales (config/scramble.php).
        Route::get('/api/_test/docs-throttled', fn () => response()->json(['ok' => true]))
            ->middleware(array_merge(['api'], config('scramble.middleware')));

        for ($attempt = 1; $attempt <= 30; $attempt++) {
            $this->withBasicAuth('docs', 'incorrecta')->get('/api/_test/docs-throttled')->assertUnauthorized();
        }

        $this->withBasicAuth('docs', 'incorrecta')->get('/api/_test/docs-throttled')->assertTooManyRequests();
    }

    private function useEnvironment(string $environment): void
    {
        $this->app['env'] = $environment;
    }

    private function configureCredentials(?string $username, ?string $password): void
    {
        config(['api-docs.username' => $username, 'api-docs.password' => $password]);
    }
}
