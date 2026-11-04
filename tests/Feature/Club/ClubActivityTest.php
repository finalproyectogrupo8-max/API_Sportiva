<?php

namespace Tests\Feature\Club;

use App\Models\Club\ClubUser;
use App\Models\Club\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** Registro de actividad del club y de la plataforma (FRO-30). */
class ClubActivityTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    public function test_role_changes_and_player_deletions_show_who_when_and_before_after(): void
    {
        $admin = User::factory()->create(['name' => 'Ana Admin']);
        $token = $this->clubToken($this->tenantA(), $admin, ['admin']);
        $member = User::factory()->create(['name' => 'Beto Profe']);
        $this->clubToken($this->tenantA(), $member, ['coach']);
        [$memberId, $player] = $this->inTenant($this->tenantA(), fn () => [
            ClubUser::where('global_user_id', $member->id)->value('id'),
            Player::factory()->create(['first_name' => 'Caro', 'last_name' => 'Jugadora']),
        ]);

        $this->withClubToken($token)->patchJson("/api/club-users/{$memberId}", ['roles' => ['sport_coordinator']])->assertOk();
        $this->withClubToken($token)->deleteJson("/api/players/{$player->id}")->assertNoContent();

        $logs = collect($this->withClubToken($token)->getJson('/api/audit-logs')->assertOk()->json('data'));
        $roles = $logs->firstWhere('action', 'club_user.roles_updated');
        $this->assertSame(['Profe'], $roles['metadata']['roles']['from']);
        $this->assertSame(['Coordinador de deporte'], $roles['metadata']['roles']['to']);
        $this->assertSame('Beto Profe', $roles['metadata']['name']);
        $this->assertSame('Ana Admin', $roles['actor']['name']);
        $this->assertNotNull($roles['created_at']);
        $this->assertSame('Caro Jugadora', $logs->firstWhere('action', 'player.deleted')['metadata']['name']);

        $this->withClubToken($token)->getJson('/api/audit-logs?type=player')->assertOk()->assertJsonCount(1, 'data');

        $coach = $this->clubToken($this->tenantA(), User::factory()->create(), ['coach']);
        $this->withClubToken($coach)->getJson('/api/audit-logs')->assertForbidden();
    }

    public function test_platform_activity_lists_who_created_what(): void
    {
        $admin = User::factory()->platformAdmin()->create(['name' => 'Plataforma']);
        Sanctum::actingAs($admin, ['platform:admin']);

        $this->postJson('/api/admin/users', ['name' => 'Nuevo', 'email' => 'nuevo@test.dev', 'password' => 'una-clave-larga-123'])->assertCreated();

        $log = collect($this->getJson('/api/admin/audit-logs?subject_type=user')->assertOk()->json('data'))->firstWhere('action', 'user.created');
        $this->assertSame('nuevo@test.dev', $log['subject_label']);
        $this->assertSame('Plataforma', $log['actor']['name']);
        $this->assertSame('nuevo@test.dev', $log['changes']['email']['to']);
    }
}
