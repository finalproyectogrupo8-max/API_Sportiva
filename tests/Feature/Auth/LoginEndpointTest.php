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
            'role' => 'admin',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'general@sportiva.com',
            'role' => 'general',
        ]);
    }

    public function test_login_endpoint_authenticates_and_returns_user_with_role(): void
    {
        $this->seed(UserSeeder::class);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@sportiva.com',
            'password' => 'admin123',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.email', 'admin@sportiva.com')
            ->assertJsonPath('user.role', 'admin')
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                ],
            ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/login', [
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
}
