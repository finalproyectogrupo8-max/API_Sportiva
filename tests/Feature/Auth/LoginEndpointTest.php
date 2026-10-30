<?php

namespace Tests\Feature\Auth;

use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_seeder_creates_admin_and_general_users(): void
    {
        $this->seed(UserSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@sportiva.com',
            'is_platform_admin' => true,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'general@sportiva.com',
            'is_platform_admin' => false,
            'is_active' => true,
        ]);
    }

    public function test_login_endpoint_authenticates_and_returns_platform_admin_flag(): void
    {
        $this->seed(UserSeeder::class);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@sportiva.com',
            'password' => 'admin123',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.email', 'admin@sportiva.com')
            ->assertJsonPath('user.is_platform_admin', true)
            ->assertJsonMissingPath('user.role')
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'is_platform_admin',
                ],
            ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@sportiva.com',
            'password' => 'wrong',
        ]);

        $response->assertUnauthorized()
            ->assertJson(['message' => 'Credenciales inválidas.']);
    }

    public function test_public_registration_is_not_available(): void
    {
        // Sin registro público: las cuentas las crea el admin o llegan por invitación.
        $payload = ['name' => 'Nuevo', 'email' => 'nuevo@sportiva.com', 'password' => 'password123'];

        $this->postJson('/api/register', $payload)->assertNotFound();
        $this->postJson('/api/auth/register', $payload)->assertNotFound();
        $this->assertDatabaseMissing('users', ['email' => 'nuevo@sportiva.com']);
    }

    public function test_legacy_login_route_was_removed(): void
    {
        // Solo queda /api/auth/login (el front lo llama desde el servidor).
        $this->postJson('/api/login', ['email' => 'a@b.com', 'password' => 'x'])->assertNotFound();
    }
}
