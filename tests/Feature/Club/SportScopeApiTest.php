<?php

namespace Tests\Feature\Club;

use App\Models\Club\Category;
use App\Models\Club\ClubUser;
use App\Models\Club\Coach;
use App\Models\Club\Player;
use App\Models\Club\PlayerCategoryMembership;
use App\Models\Club\Season;
use App\Models\Club\Sport;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class SportScopeApiTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    public function test_sport_coordinator_only_sees_categories_players_and_coaches_of_assigned_sports(): void
    {
        $user = User::factory()->create();
        $token = $this->clubToken($this->tenantA(), $user, ['sport_coordinator']);

        [$footballCat, $basketballCat, $footballPlayer, $basketballPlayer, $footballCoach, $basketballCoach] = $this->inTenant($this->tenantA(), function () use ($user) {
            $football = Sport::factory()->create(['name' => 'Fútbol']);
            $basketball = Sport::factory()->create(['name' => 'Básquet']);
            $season = Season::factory()->create(['name' => '2027']);

            $clubUser = ClubUser::query()->where('global_user_id', $user->id)->sole();
            $clubUser->sports()->attach($football->id);

            $footballCat = Category::factory()->create([
                'name' => 'Fútbol U15',
                'sport_id' => $football->id,
                'season_id' => $season->id,
            ]);
            $basketballCat = Category::factory()->create([
                'name' => 'Básquet U15',
                'sport_id' => $basketball->id,
                'season_id' => $season->id,
            ]);

            $footballPlayer = Player::factory()->create(['first_name' => 'Lionel', 'last_name' => 'Messi']);
            $basketballPlayer = Player::factory()->create(['first_name' => 'Manu', 'last_name' => 'Ginóbili']);

            PlayerCategoryMembership::create([
                'player_id' => $footballPlayer->id,
                'category_id' => $footballCat->id,
                'start_date' => '2027-01-01',
                'status' => 'active',
            ]);
            PlayerCategoryMembership::create([
                'player_id' => $basketballPlayer->id,
                'category_id' => $basketballCat->id,
                'start_date' => '2027-01-01',
                'status' => 'active',
            ]);

            $footballCoach = Coach::factory()->create(['first_name' => 'Lionel', 'last_name' => 'Scaloni']);
            $basketballCoach = Coach::factory()->create(['first_name' => 'Sergio', 'last_name' => 'Hernández']);

            DB::connection('tenant')->table('category_coaches')->insert([
                'category_id' => $footballCat->id,
                'coach_id' => $footballCoach->id,
                'assignment_type' => 'head_coach',
            ]);
            DB::connection('tenant')->table('category_coaches')->insert([
                'category_id' => $basketballCat->id,
                'coach_id' => $basketballCoach->id,
                'assignment_type' => 'head_coach',
            ]);

            return [$footballCat, $basketballCat, $footballPlayer, $basketballPlayer, $footballCoach, $basketballCoach];
        });

        // Categories: only football
        $this->withClubToken($token)->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Fútbol U15');

        $this->withClubToken($token)->getJson("/api/categories/{$footballCat->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Fútbol U15');

        $this->withClubToken($token)->getJson("/api/categories/{$basketballCat->id}")
            ->assertNotFound();

        // Players: only football player
        $this->withClubToken($token)->getJson('/api/players')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.last_name', 'Messi');

        $this->withClubToken($token)->getJson("/api/players/{$footballPlayer->id}")
            ->assertOk()
            ->assertJsonPath('data.last_name', 'Messi');

        $this->withClubToken($token)->getJson("/api/players/{$basketballPlayer->id}")
            ->assertNotFound();

        // Coaches: only football coach
        $this->withClubToken($token)->getJson('/api/coaches')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.last_name', 'Scaloni');

        $this->withClubToken($token)->getJson("/api/coaches/{$basketballCoach->id}")
            ->assertNotFound();
    }

    public function test_broadest_scope_wins_when_user_has_multiple_roles(): void
    {
        $user = User::factory()->create();
        // sport_coordinator gives sport scope, coordinator gives all scope
        $token = $this->clubToken($this->tenantA(), $user, ['sport_coordinator', 'coordinator']);

        $this->inTenant($this->tenantA(), function () use ($user) {
            $football = Sport::factory()->create(['name' => 'Fútbol']);
            $basketball = Sport::factory()->create(['name' => 'Básquet']);
            $season = Season::factory()->create(['name' => '2027']);

            $clubUser = ClubUser::query()->where('global_user_id', $user->id)->sole();
            $clubUser->sports()->attach($football->id);

            Category::factory()->create(['name' => 'Fútbol U15', 'sport_id' => $football->id, 'season_id' => $season->id]);
            Category::factory()->create(['name' => 'Básquet U15', 'sport_id' => $basketball->id, 'season_id' => $season->id]);
        });

        // Scope priority: all wins over sport!
        $me = $this->withClubToken($token)->getJson('/api/me')->assertOk();
        $this->assertSame('all', $me->json('permission_scopes')['categories.view']);
        $this->assertSame('all', $me->json('permission_scopes')['players.view']);

        $this->withClubToken($token)->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_sport_scope_wins_over_own_scope(): void
    {
        $user = User::factory()->create();
        // sport_coordinator (sport) + coach (own) -> sport wins!
        $token = $this->clubToken($this->tenantA(), $user, ['sport_coordinator', 'coach']);

        $this->inTenant($this->tenantA(), function () use ($user) {
            $football = Sport::factory()->create(['name' => 'Fútbol']);
            $basketball = Sport::factory()->create(['name' => 'Básquet']);
            $season = Season::factory()->create(['name' => '2027']);

            $clubUser = ClubUser::query()->where('global_user_id', $user->id)->sole();
            $clubUser->sports()->attach($football->id);

            $footballCat = Category::factory()->create(['name' => 'Fútbol U15', 'sport_id' => $football->id, 'season_id' => $season->id]);
            $basketballCat = Category::factory()->create(['name' => 'Básquet U15', 'sport_id' => $basketball->id, 'season_id' => $season->id]);

            // User is coach of Basketball, but coordinator of Football
            $coach = Coach::factory()->create(['club_user_id' => $clubUser->id]);
            DB::connection('tenant')->table('category_coaches')->insert([
                'category_id' => $basketballCat->id,
                'coach_id' => $coach->id,
                'assignment_type' => 'head_coach',
            ]);
        });

        // sport (weight 2) > own (weight 1) -> scope is sport for categories.view
        $me = $this->withClubToken($token)->getJson('/api/me')->assertOk();
        $this->assertSame('sport', $me->json('permission_scopes')['categories.view']);

        // Visible categories are Football (from sport scope)
        $this->withClubToken($token)->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Fútbol U15');
    }

    public function test_sport_scope_without_assigned_sports_sees_empty_list_and_404(): void
    {
        $user = User::factory()->create();
        $token = $this->clubToken($this->tenantA(), $user, ['sport_coordinator']);

        $cat = $this->inTenant($this->tenantA(), function () {
            $sport = Sport::factory()->create(['name' => 'Fútbol']);
            $season = Season::factory()->create();

            return Category::factory()->create(['sport_id' => $sport->id, 'season_id' => $season->id]);
        });

        $this->withClubToken($token)->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->withClubToken($token)->getJson("/api/categories/{$cat->id}")
            ->assertNotFound();

        $this->withClubToken($token)->getJson('/api/players')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_me_returns_assigned_sports_for_selector(): void
    {
        $user = User::factory()->create();
        $token = $this->clubToken($this->tenantA(), $user, ['sport_coordinator']);

        [$football, $volleyball] = $this->inTenant($this->tenantA(), function () use ($user) {
            $football = Sport::factory()->create(['name' => 'Fútbol']);
            $volleyball = Sport::factory()->create(['name' => 'Vóley']);

            $clubUser = ClubUser::query()->where('global_user_id', $user->id)->sole();
            $clubUser->sports()->attach([$football->id, $volleyball->id]);

            return [$football, $volleyball];
        });

        $this->withClubToken($token)->getJson('/api/me')
            ->assertOk()
            ->assertJsonCount(2, 'sports')
            ->assertJsonPath('sports.0.id', $football->id)
            ->assertJsonPath('sports.0.name', 'Fútbol')
            ->assertJsonPath('sports.1.id', $volleyball->id)
            ->assertJsonPath('sports.1.name', 'Vóley');
    }

    public function test_platform_admin_membership_endpoints_assign_and_update_sports(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        Sanctum::actingAs($admin, ['*']);

        [$football, $basketball] = $this->inTenant($this->tenantA(), function () {
            return [
                Sport::factory()->create(['name' => 'Fútbol']),
                Sport::factory()->create(['name' => 'Básquet']),
            ];
        });

        $user = User::factory()->create();

        // 1. Create membership with sport_ids
        $response = $this->postJson("/api/admin/tenants/{$this->tenantA()->id}/memberships", [
            'user_id' => $user->id,
            'roles' => ['sport_coordinator'],
            'sport_ids' => [$football->id],
        ])
            ->assertCreated()
            ->assertJsonPath('data.roles.0.slug', 'sport_coordinator')
            ->assertJsonCount(1, 'data.sports')
            ->assertJsonPath('data.sports.0.id', $football->id)
            ->assertJsonPath('data.sports.0.name', 'Fútbol');

        $membershipId = $response->json('data.id');

        // Verify in tenant DB
        $sports = $this->inTenant($this->tenantA(), fn () => ClubUser::query()
            ->where('global_user_id', $user->id)
            ->first()
            ->sports
            ->pluck('id')
            ->all());
        $this->assertSame([$football->id], $sports);

        // 2. Update membership sports
        $this->patchJson("/api/admin/tenants/{$this->tenantA()->id}/memberships/{$membershipId}", [
            'sport_ids' => [$basketball->id],
        ])
            ->assertOk()
            ->assertJsonCount(1, 'data.sports')
            ->assertJsonPath('data.sports.0.id', $basketball->id);

        $updatedSports = $this->inTenant($this->tenantA(), fn () => ClubUser::query()
            ->where('global_user_id', $user->id)
            ->first()
            ->sports
            ->pluck('id')
            ->all());
        $this->assertSame([$basketball->id], $updatedSports);
    }

    public function test_platform_admin_membership_rejects_invalid_sport_id_and_rolls_back(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        Sanctum::actingAs($admin, ['*']);

        $user = User::factory()->create();

        $this->postJson("/api/admin/tenants/{$this->tenantA()->id}/memberships", [
            'user_id' => $user->id,
            'roles' => ['sport_coordinator'],
            'sport_ids' => [999999],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sport_ids');

        // Central TenantMembership was rolled back and not created
        $this->assertDatabaseMissing('tenant_memberships', [
            'user_id' => $user->id,
            'tenant_id' => $this->tenantA()->id,
        ]);
    }
}
