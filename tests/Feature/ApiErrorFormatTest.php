<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

/**
 * Las rutas /api/* responden JSON también en los errores, aunque el cliente no envíe
 * "Accept: application/json". Por eso estos tests usan get()/post() y no getJson().
 */
class ApiErrorFormatTest extends TestCase
{
    public function test_unknown_api_route_returns_json_404(): void
    {
        $this->get('/api/no-existe')
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure(['message']);
    }

    public function test_unsupported_method_returns_json_405(): void
    {
        $this->post('/api/health')
            ->assertStatus(405)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure(['message']);
    }

    public function test_validation_errors_use_laravel_format(): void
    {
        Route::post('/api/_test/validation', fn (Request $request) => $request->validate([
            'email' => ['required', 'email'],
        ]))->middleware('api');

        $this->post('/api/_test/validation', ['email' => 'no-es-un-email'])
            ->assertStatus(422)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure(['message', 'errors' => ['email']]);
    }

    public function test_unhandled_exception_hides_details_when_debug_is_off(): void
    {
        config(['app.debug' => false]);
        Route::get('/api/_test/boom', fn () => throw new RuntimeException('detalle interno secreto'))
            ->middleware('api');

        $this->get('/api/_test/boom')
            ->assertStatus(500)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure(['message'])
            ->assertJsonMissingPath('exception')
            ->assertJsonMissingPath('file')
            ->assertJsonMissingPath('line')
            ->assertJsonMissingPath('trace')
            ->assertDontSee('detalle interno secreto');
    }
}
