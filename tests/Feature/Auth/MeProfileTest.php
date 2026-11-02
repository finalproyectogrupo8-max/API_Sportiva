<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** Mi perfil (FRO-28): nombre, contraseña, clubes con rol y cerrar las demás sesiones. */
class MeProfileTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    public function test_changes_the_name_and_lists_clubs_with_roles(): void
    {
        $user = User::factory()->create(['name' => 'Ana']);
        $token = $this->clubToken($this->tenantA(), $user, ['coach']);
        $this->clubToken($this->tenantB(), $user, ['admin', 'coordinator']);

        $this->withClubToken($token)->patchJson('/api/me', ['name' => '  Ana Pérez '])->assertOk()->assertJsonPath('data.name', 'Ana Pérez');
        $this->withClubToken($token)->patchJson('/api/me', ['name' => ''])->assertUnprocessable()->assertJsonValidationErrors('name');

        $clubs = collect($this->withClubToken($token)->getJson('/api/me/clubs')->assertOk()->json('data'))->keyBy('slug');
        $this->assertCount(2, $clubs);
        $this->assertSame(['Profe'], $clubs[$this->tenantA()->slug]['roles']);
        $this->assertCount(2, $clubs[$this->tenantB()->slug]['roles']);
    }

    public function test_changes_the_password_with_the_current_one_and_closes_other_sessions(): void
    {
        $user = User::factory()->create(['password' => Hash::make('vieja-contraseña-123')]);
        $other = $this->clubToken($this->tenantA(), $user, ['coach']);
        $token = $this->clubToken($this->tenantA(), $user);

        $this->withClubToken($token)->putJson('/api/me/password', ['current_password' => 'mal', 'password' => 'nueva-contraseña-456', 'password_confirmation' => 'nueva-contraseña-456'])
            ->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->withClubToken($token)->putJson('/api/me/password', ['current_password' => 'vieja-contraseña-123', 'password' => 'corta', 'password_confirmation' => 'corta'])
            ->assertUnprocessable()->assertJsonValidationErrors('password');

        $this->withClubToken($token)->putJson('/api/me/password', ['current_password' => 'vieja-contraseña-123', 'password' => 'nueva-contraseña-456', 'password_confirmation' => 'nueva-contraseña-456'])
            ->assertOk()->assertJsonPath('data.closed_sessions', 1);

        $this->assertTrue(Hash::check('nueva-contraseña-456', $user->fresh()->password));
        $this->assertSame(1, $user->tokens()->count());
        $this->withClubToken($other)->getJson('/api/me/clubs')->assertUnauthorized();
        $this->withClubToken($token)->getJson('/api/me/clubs')->assertOk();
    }

    public function test_logout_all_keeps_the_current_session(): void
    {
        $user = User::factory()->create();
        $this->clubToken($this->tenantA(), $user, ['coach']);
        $this->clubToken($this->tenantA(), $user);
        $token = $this->clubToken($this->tenantA(), $user);

        $this->withClubToken($token)->postJson('/api/me/logout-all')->assertOk()->assertJsonPath('data.closed_sessions', 2);
        $this->assertSame(1, $user->tokens()->count());
    }
}
