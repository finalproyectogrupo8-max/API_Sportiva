<?php

namespace Tests\Feature\Club;

use App\Club\Coaches\CoachAccounts;
use App\Models\Club\Category;
use App\Models\Club\ClubUser;
use App\Models\Club\Coach;
use App\Models\Club\Season;
use App\Models\Club\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** El usuario con rol de profe queda vinculado con su ficha: sin eso no tiene categorías a cargo. */
class CoachAccountLinkTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    public function test_an_invited_coach_gets_a_linked_profile_and_creates_matches_of_their_category(): void
    {
        Notification::fake();
        $admin = $this->clubToken($this->tenantA(), roles: ['admin']);

        [$ownCategory, $otherCategory] = $this->inTenant($this->tenantA(), function () {
            $sport = Sport::factory()->create();
            $season = Season::factory()->create();

            return [
                Category::factory()->create(['sport_id' => $sport->id, 'season_id' => $season->id]),
                Category::factory()->create(['sport_id' => $sport->id, 'season_id' => $season->id]),
            ];
        });

        $this->withClubToken($admin)->postJson('/api/club-users', ['email' => 'juan@club.test', 'name' => 'Juan Pérez', 'roles' => ['coach']])
            ->assertCreated();

        $coach = $this->inTenant($this->tenantA(), fn () => Coach::query()->where('email', 'juan@club.test')->sole());
        $this->assertNotNull($coach->club_user_id);
        $this->assertSame(['Juan', 'Pérez'], [$coach->first_name, $coach->last_name]);

        $this->withClubToken($admin)->postJson("/api/categories/{$ownCategory->id}/coaches", ['coach_id' => $coach->id, 'assignment_type' => 'head_coach'])
            ->assertCreated();

        // Acepta la invitación: recién ahí queda activo.
        $juan = User::query()->where('email', 'juan@club.test')->sole();
        $juan->forceFill(['is_active' => true, 'invite_accepted_at' => now()])->save();
        $profe = $this->clubToken($this->tenantA(), $juan);
        $match = fn (Category $category) => ['category_id' => $category->id, 'opponent' => 'Rival', 'match_date' => now()->addWeek()->toDateTimeString(), 'is_home' => true];
        $this->withClubToken($profe)->postJson('/api/matches', $match($ownCategory))->assertCreated();
        $this->withClubToken($profe)->postJson('/api/matches', $match($otherCategory))->assertNotFound();
    }

    public function test_an_unlinked_profile_with_the_same_email_is_reused(): void
    {
        Notification::fake();
        $admin = $this->clubToken($this->tenantA(), roles: ['admin']);
        $existing = $this->inTenant($this->tenantA(), fn () => Coach::factory()->create(['email' => 'Ana@Club.test', 'club_user_id' => null]));

        $this->withClubToken($admin)->postJson('/api/club-users', ['email' => 'ana@club.test', 'name' => 'Ana Díaz', 'roles' => ['physical_trainer']])->assertCreated();

        $this->inTenant($this->tenantA(), function () use ($existing) {
            $this->assertSame(1, Coach::query()->whereRaw('LOWER(email) = ?', ['ana@club.test'])->count());
            $this->assertNotNull($existing->refresh()->club_user_id);
        });
    }

    public function test_a_profile_created_with_a_club_user_email_is_linked(): void
    {
        $admin = $this->clubToken($this->tenantA(), roles: ['admin']);
        $user = User::factory()->create(['email' => 'lu@club.test']);
        $this->clubToken($this->tenantA(), $user, ['coach']);

        $id = $this->withClubToken($admin)->postJson('/api/coaches', ['first_name' => 'Lucía', 'last_name' => 'Gómez', 'email' => 'lu@club.test', 'status' => 'active'])
            ->assertCreated()->json('data.id');

        $this->inTenant($this->tenantA(), fn () => $this->assertSame(
            ClubUser::query()->where('global_user_id', $user->id)->value('id'),
            Coach::query()->find($id)->club_user_id,
        ));
    }

    public function test_backfill_links_existing_coaches_once(): void
    {
        $user = User::factory()->create(['name' => 'Pedro Ruiz']);
        $this->clubToken($this->tenantA(), $user, ['coach']);
        $this->clubToken($this->tenantA(), roles: ['inventory_manager']);

        $this->inTenant($this->tenantA(), function () use ($user) {
            $accounts = app(CoachAccounts::class);
            $this->assertSame(1, $accounts->backfill());
            $this->assertSame(0, $accounts->backfill());
            $clubUserId = ClubUser::query()->where('global_user_id', $user->id)->value('id');
            $this->assertSame('Pedro', Coach::query()->where('club_user_id', $clubUserId)->sole()->first_name);
        });
    }
}
