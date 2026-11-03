<?php

namespace Tests\Feature\Club;

use App\Models\Club\ClubUser;
use App\Models\Club\Permission;
use App\Models\Club\Role;
use App\Models\TenantMembership;
use App\Models\User;
use App\Notifications\UserInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** Equipo del club gestionado por su administrador (TIZ-34). */
class ClubUserApiTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    private function admin(): array
    {
        $user = User::factory()->create(['email' => 'admin@club.test']);

        return [$user, $this->clubToken($this->tenantA(), $user, ['admin'])];
    }

    private function clubUserId(User $user): int
    {
        return $this->inTenant($this->tenantA(), fn () => ClubUser::where('global_user_id', $user->id)->value('id'));
    }

    public function test_the_admin_invites_a_new_person_who_gets_the_mail_to_choose_a_password(): void
    {
        Notification::fake();
        [, $token] = $this->admin();

        $this->withClubToken($token)->postJson('/api/club-users', ['email' => 'nueva@club.test', 'roles' => ['coach']])
            ->assertUnprocessable()->assertJsonValidationErrors('name');

        $this->withClubToken($token)->postJson('/api/club-users', ['email' => 'Nueva@Club.test', 'name' => 'Marta Profe', 'roles' => ['coach']])
            ->assertCreated()
            ->assertJsonPath('meta.invited', true)
            ->assertJsonPath('data.email', 'nueva@club.test')
            ->assertJsonPath('data.status', 'invited')
            ->assertJsonPath('data.roles.0.slug', 'coach');

        $user = User::where('email', 'nueva@club.test')->firstOrFail();
        Notification::assertSentTo($user, UserInvitationNotification::class);
        $this->assertTrue(TenantMembership::where('tenant_id', $this->tenantA()->id)->where('user_id', $user->id)->where('is_active', true)->exists());

        $this->withClubToken($token)->postJson('/api/club-users', ['email' => 'nueva@club.test', 'roles' => ['coach']])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_an_existing_user_is_added_without_a_new_account_or_mail(): void
    {
        Notification::fake();
        [, $token] = $this->admin();
        $existing = User::factory()->create(['email' => 'ya@club.test']);

        $this->withClubToken($token)->postJson('/api/club-users', ['email' => 'ya@club.test', 'roles' => ['coordinator']])
            ->assertCreated()->assertJsonPath('meta.invited', false)->assertJsonPath('data.roles.0.slug', 'coordinator');

        Notification::assertNothingSent();
        $this->assertSame(1, User::where('email', 'ya@club.test')->count());
        $this->assertNotNull($this->clubUserId($existing));
    }

    public function test_roles_change_access_is_removed_and_the_last_admin_stays(): void
    {
        [$adminUser, $token] = $this->admin();
        $member = User::factory()->create();
        $this->clubToken($this->tenantA(), $member, ['coach']);
        $id = $this->clubUserId($member);

        $this->withClubToken($token)->patchJson("/api/club-users/{$id}", ['roles' => ['coach', 'physical_trainer']])
            ->assertOk()->assertJsonCount(2, 'data.roles');

        $this->withClubToken($token)->patchJson("/api/club-users/{$id}", ['status' => 'disabled'])
            ->assertOk()->assertJsonPath('data.status', 'disabled');
        $this->assertFalse(TenantMembership::where('tenant_id', $this->tenantA()->id)->where('user_id', $member->id)->value('is_active'));

        $this->withClubToken($token)->patchJson("/api/club-users/{$id}", ['status' => 'active'])->assertOk()->assertJsonPath('data.status', 'active');

        // El único administrador no se puede sacar el rol ni el acceso.
        $self = $this->clubUserId($adminUser);
        $this->withClubToken($token)->patchJson("/api/club-users/{$self}", ['roles' => ['coach']])->assertStatus(409)->assertJsonPath('code', 'last_admin');
        $this->withClubToken($token)->patchJson("/api/club-users/{$self}", ['status' => 'disabled'])->assertStatus(409);

        $team = $this->withClubToken($token)->getJson('/api/club-users?team=1')->assertOk()->json('data');
        $this->assertCount(2, $team);
        $this->assertTrue(collect($team)->firstWhere('id', $self)['is_self']);
        $this->assertArrayHasKey('email', $team[0]);

        // La lista básica sigue sin mails ni roles.
        $this->assertSame(['id', 'display_name'], array_keys($this->withClubToken($token)->getJson('/api/club-users')->json('data.0')));
    }

    public function test_no_permission_and_no_escalation(): void
    {
        $coach = User::factory()->create();
        $coachToken = $this->clubToken($this->tenantA(), $coach, ['coach']);
        $this->withClubToken($coachToken)->getJson('/api/club-users?team=1')->assertForbidden();
        $this->withClubToken($coachToken)->postJson('/api/club-users', ['email' => 'x@club.test', 'name' => 'X', 'roles' => ['coach']])->assertForbidden();

        // Un rol propio que puede invitar, pero no tiene los permisos del administrador.
        $this->inTenant($this->tenantA(), function () {
            $role = Role::create(['slug' => 'secretaria', 'name' => 'Secretaría', 'is_system' => false]);
            $role->permissions()->attach(Permission::whereIn('name', ['club_users.view', 'club_users.invite', 'club_users.update', 'players.view'])->pluck('id'), ['scope' => 'all']);
        });
        $secretary = User::factory()->create();
        $token = $this->clubToken($this->tenantA(), $secretary, ['secretaria']);

        $this->withClubToken($token)->postJson('/api/club-users', ['email' => 'x@club.test', 'name' => 'X', 'roles' => ['admin']])->assertForbidden();
        $this->withClubToken($token)->postJson('/api/club-users', ['email' => 'x@club.test', 'name' => 'X', 'roles' => ['secretaria']])->assertCreated();
        $this->withClubToken($token)->patchJson('/api/club-users/'.$this->clubUserId($coach), ['roles' => ['secretaria']])->assertForbidden();
    }
}
