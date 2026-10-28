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

    public function test_register_creates_general_user_and_returns_201(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Nuevo Socio',
            'email' => 'socio@sportiva.com',
            'password' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'socio@sportiva.com')
            ->assertJsonPath('user.role', 'general')
            ->assertJsonStructure([
                'message',
                'token',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'socio@sportiva.com',
            'role' => 'general',
        ]);
    }

    public function test_register_validates_required_fields_and_unique_email(): void
    {
        $response = $this->postJson('/api/register', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }
}
