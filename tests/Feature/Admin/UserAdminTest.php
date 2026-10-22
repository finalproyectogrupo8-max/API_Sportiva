<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class UserAdminTest extends TestCase
{
    use RefreshDatabase;

    private function asAdmin(): User
    {
        $admin = User::factory()->platformAdmin()->create();
        Sanctum::actingAs($admin, ['*']);

        return $admin;
    }

    public function test_index_is_paginated_and_clamps_per_page(): void
    {
        User::factory()->count(25)->create();
        $this->asAdmin();

        $this->getJson('/api/admin/users')
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 26)
            ->assertJsonStructure(['data' => [['id', 'name', 'email']], 'links', 'meta']);

        $this->getJson('/api/admin/users?per_page=500')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100);
        $this->getJson('/api/admin/users?per_page=0')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 1);
    }

    public function test_index_filters_searches_by_name_and_email_and_sorts(): void
    {
        $namedUser = User::factory()->create([
            'name' => 'River Test',
            'email' => 'river@example.test',
        ]);
        $emailUser = User::factory()->create([
            'name' => 'Other User',
            'email' => 'boca@example.test',
        ]);
        $inactiveUser = User::factory()->inactive()->create([
            'name' => 'Inactive User',
            'email' => 'inactive@example.test',
        ]);
        $this->asAdmin();

        $this->getJson('/api/admin/users?search=River')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $namedUser->id);
        $this->getJson('/api/admin/users?search=boca@')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $emailUser->id);
        $this->getJson('/api/admin/users?is_active=false')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $inactiveUser->id);
        $sortedEmails = $this->getJson('/api/admin/users?sort=-email&per_page=100')
            ->assertOk()
            ->json('data.*.email');
        $expectedEmails = $sortedEmails;
        sort($expectedEmails, SORT_STRING);
        $this->assertSame(array_reverse($expectedEmails), $sortedEmails);
        $this->getJson('/api/admin/users?sort=password')
            ->assertUnprocessable()->assertJsonValidationErrors('sort');
    }

    public function test_show_returns_resource_fields_without_credentials(): void
    {
        $user = User::factory()->create();
        $this->asAdmin();

        $this->getJson("/api/admin/users/{$user->id}")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'is_active',
                    'is_platform_admin',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.remember_token');
    }

    public function test_store_creates_active_non_admin_user_with_hashed_password(): void
    {
        $admin = $this->asAdmin();

        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->postJson('/api/admin/users', [
                'name' => 'New User',
                'email' => 'new@example.test',
                'password' => 'correct-horse-battery',
            ])
            ->assertCreated()
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.is_platform_admin', false)
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.remember_token');

        $user = User::query()->where('email', 'new@example.test')->sole();
        $this->assertTrue($user->is_active);
        $this->assertFalse($user->is_platform_admin);
        $this->assertTrue(Hash::check('correct-horse-battery', $user->password));

        $log = AuditLog::query()->sole();
        $this->assertSame($admin->id, $log->actor_id);
        $this->assertSame($response->json('data.id'), $log->subject_id);
        $this->assertSame('203.0.113.9', $log->ip);
        $this->assertSame('user.created', $log->action);
        $this->assertArrayNotHasKey('password', $log->changes);
        $this->assertStringNotContainsString('correct-horse-battery', (string) json_encode($log->changes));
    }

    public function test_store_validates_required_fields_email_and_password(): void
    {
        User::factory()->create(['email' => 'used@example.test']);
        $this->asAdmin();

        $this->postJson('/api/admin/users', [
            'email' => 'missing-name@example.test',
            'password' => 'correct-horse-battery',
        ])->assertUnprocessable()->assertJsonValidationErrors('name');

        $this->postJson('/api/admin/users', [
            'name' => 'Duplicate',
            'email' => 'used@example.test',
            'password' => 'correct-horse-battery',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->postJson('/api/admin/users', [
            'name' => 'Invalid',
            'email' => 'not-an-email',
            'password' => 'correct-horse-battery',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->postJson('/api/admin/users', [
            'name' => 'Short Password',
            'email' => 'short@example.test',
            'password' => '12345678901',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
    }

    public function test_store_rejects_platform_admin_and_active_flags(): void
    {
        $this->asAdmin();

        $this->postJson('/api/admin/users', [
            'name' => 'Admin',
            'email' => 'admin-user@example.test',
            'password' => 'correct-horse-battery',
            'is_platform_admin' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('is_platform_admin');

        $this->postJson('/api/admin/users', [
            'name' => 'Inactive',
            'email' => 'inactive-user@example.test',
            'password' => 'correct-horse-battery',
            'is_active' => false,
        ])->assertUnprocessable()->assertJsonValidationErrors('is_active');
    }

    public function test_update_changes_only_submitted_fields_and_rejects_platform_admin(): void
    {
        $user = User::factory()->create([
            'name' => 'Original',
            'password' => 'correct-horse-battery',
        ]);
        $this->asAdmin();

        $this->patchJson("/api/admin/users/{$user->id}", ['name' => 'Updated'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => $user->email,
            'is_active' => true,
        ]);

        $this->patchJson("/api/admin/users/{$user->id}", ['is_platform_admin' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('is_platform_admin');
    }

    public function test_update_rejects_duplicate_email_and_accepts_the_users_own_email(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->asAdmin();

        $this->patchJson("/api/admin/users/{$user->id}", ['email' => $otherUser->email])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->patchJson("/api/admin/users/{$user->id}", ['email' => $user->email])->assertOk();
    }

    public function test_requires_authentication_for_user_endpoints(): void
    {
        $user = User::factory()->create();

        $this->getJson('/api/admin/users')->assertUnauthorized();
        $this->postJson('/api/admin/users', [])->assertUnauthorized();
        $this->getJson("/api/admin/users/{$user->id}")->assertUnauthorized();
        $this->patchJson("/api/admin/users/{$user->id}", [])->assertUnauthorized();
    }

    public function test_forbids_non_platform_admin_for_user_endpoints(): void
    {
        $target = User::factory()->create();
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $this->getJson('/api/admin/users')->assertForbidden()->assertJsonPath('code', 'forbidden');
        $this->postJson('/api/admin/users', [
            'name' => 'Denied',
            'email' => 'denied@example.test',
            'password' => 'correct-horse-battery',
        ])->assertForbidden()->assertJsonPath('code', 'forbidden');
        $this->getJson("/api/admin/users/{$target->id}")
            ->assertForbidden()->assertJsonPath('code', 'forbidden');
        $this->patchJson("/api/admin/users/{$target->id}", ['name' => 'Denied'])
            ->assertForbidden()->assertJsonPath('code', 'forbidden');
    }

    public function test_show_and_update_return_json_404_for_missing_user(): void
    {
        $this->asAdmin();

        $this->getJson('/api/admin/users/999999')->assertNotFound()->assertJsonStructure(['message']);
        $this->patchJson('/api/admin/users/999999', ['name' => 'Missing'])
            ->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_deactivation_revokes_all_tokens_and_prevents_login(): void
    {
        $this->asAdmin();
        $user = User::factory()->create();
        $firstToken = $user->createToken('first')->plainTextToken;
        $user->createToken('second');

        $this->patchJson("/api/admin/users/{$user->id}", ['is_active' => false])
            ->assertOk()->assertJsonPath('data.is_active', false);

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $this->withToken($firstToken)->getJson('/api/me/tenants')->assertUnauthorized();
        $this->flushHeaders();
        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertUnauthorized();
    }

    public function test_password_change_revokes_tokens_and_new_password_can_log_in(): void
    {
        $this->asAdmin();
        $user = User::factory()->create();
        $user->createToken('existing');
        $newPassword = 'new-password-that-is-long';

        $this->patchJson("/api/admin/users/{$user->id}", ['password' => $newPassword])
            ->assertOk()
            ->assertJsonMissingPath('data.password');

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertSame(['changed' => true], AuditLog::query()->sole()->changes['password']);
        $this->assertStringNotContainsString(
            $newPassword,
            (string) json_encode(AuditLog::query()->sole()->changes),
        );
        $this->assertStringNotContainsString(
            $user->getRawOriginal('password'),
            (string) json_encode(AuditLog::query()->sole()->changes),
        );

        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => $newPassword,
        ])->assertOk();
    }

    public function test_admin_cannot_deactivate_themselves(): void
    {
        $admin = $this->asAdmin();

        $this->patchJson("/api/admin/users/{$admin->id}", ['is_active' => false])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_active');

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_reactivation_does_not_restore_revoked_tokens(): void
    {
        $this->asAdmin();
        $user = User::factory()->inactive()->create();

        $this->patchJson("/api/admin/users/{$user->id}", ['is_active' => true])
            ->assertOk()->assertJsonPath('data.is_active', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_update_audit_contains_only_changed_fields_and_skips_no_op(): void
    {
        $user = User::factory()->create([
            'name' => 'Original',
            'password' => 'correct-horse-battery',
        ]);
        $this->asAdmin();
        $user->createToken('update-test');

        $this->patchJson("/api/admin/users/{$user->id}", ['name' => 'Updated'])->assertOk();

        $log = AuditLog::query()->sole();
        $changes = $log->changes;
        $this->assertSame('user.updated', $log->action);
        $this->assertSame(['name'], array_keys($changes));
        $this->assertSame('Original', $changes['name']['from']);
        $this->assertSame('Updated', $changes['name']['to']);
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->patchJson("/api/admin/users/{$user->id}", [
            'name' => 'Updated',
            'password' => 'correct-horse-battery',
        ])->assertOk();
        $this->assertDatabaseCount('audit_logs', 1);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_audit_failure_rolls_back_user_creation(): void
    {
        $this->asAdmin();
        $userCount = User::query()->count();

        $this->mock(AuditLogger::class)
            ->shouldReceive('recordCreated')
            ->once()
            ->andThrow(new RuntimeException('Audit storage unavailable.'));

        $this->postJson('/api/admin/users', [
            'name' => 'Rolled Back',
            'email' => 'rolled-back@example.test',
            'password' => 'correct-horse-battery',
        ])->assertInternalServerError();

        $this->assertSame($userCount, User::query()->count());
        $this->assertDatabaseCount('audit_logs', 0);
    }
}
