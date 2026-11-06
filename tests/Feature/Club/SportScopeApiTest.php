<?php

namespace Tests\Feature\Club;

use App\Models\Club\Category;
use App\Models\Club\ClubUser;
use App\Models\Club\Coach;
use App\Models\Club\Competition;
use App\Models\Club\CompetitionStanding;
use App\Models\Club\GameMatch;
use App\Models\Club\Order;
use App\Models\Club\Player;
use App\Models\Club\PlayerCategoryMembership;
use App\Models\Club\Season;
use App\Models\Club\SeasonObjective;
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

    public function test_sport_coordinator_only_receives_read_permissions_limited_to_their_sport(): void
    {
        $token = $this->clubToken($this->tenantA(), roles: ['sport_coordinator']);

        $scopes = $this->withClubToken($token)->getJson('/api/me')->assertOk()->json('permission_scopes');

        // Todo lo deportivo, limitado a su deporte.
        foreach (['players.view', 'matches.view', 'trainings.view', 'attendance.view', 'callups.view', 'coaches.view',
            'physical_evaluations.view', 'player_requirements.view', 'project.view', 'apparel_orders.view', 'material_orders.view',
            'categories.view', 'competitions.view'] as $permission) {
            $this->assertSame('sport', $scopes[$permission] ?? null, $permission);
        }
        // Lo compartido del club, que no es de ningún deporte.
        $this->assertSame('all', $scopes['inventory.view']);
        $this->assertSame('all', $scopes['facilities.view']);
        // Solo consulta: nada de cargar, aprobar, gestionar el equipo ni configurar el club.
        foreach (array_keys($scopes) as $permission) {
            $this->assertStringEndsWith('.view', $permission);
        }
        foreach (['club_users.view', 'roles.view', 'audit.view', 'sports.view', 'venues.view', 'announcements.view'] as $permission) {
            $this->assertArrayNotHasKey($permission, $scopes);
        }
    }

    public function test_sport_coordinator_cannot_modify_seasons_categories_or_competitions(): void
    {
        $user = User::factory()->create();
        $token = $this->clubToken($this->tenantA(), $user, ['sport_coordinator']);

        [$season, $football, $footballCategory, $basketballCategory, $footballCompetition, $basketballCompetition] = $this->inTenant($this->tenantA(), function () use ($user) {
            $football = Sport::factory()->create(['name' => 'Fútbol']);
            $basketball = Sport::factory()->create(['name' => 'Básquet']);
            $season = Season::factory()->create(['name' => '2027']);
            $clubUser = ClubUser::query()->where('global_user_id', $user->id)->sole();
            $clubUser->sports()->attach($football->id);

            $footballCategory = Category::factory()->create([
                'sport_id' => $football->id,
                'season_id' => $season->id,
                'name' => 'Fútbol U15',
            ]);
            $basketballCategory = Category::factory()->create([
                'sport_id' => $basketball->id,
                'season_id' => $season->id,
                'name' => 'Básquet U15',
            ]);

            return [
                $season,
                $football,
                $footballCategory,
                $basketballCategory,
                Competition::factory()->create(['sport_id' => $football->id, 'season_id' => $season->id]),
                Competition::factory()->create(['sport_id' => $basketball->id, 'season_id' => $season->id]),
            ];
        });

        $this->withClubToken($token)->getJson('/api/seasons')
            ->assertOk()->assertJsonCount(1, 'data');
        $this->withClubToken($token)->getJson('/api/categories')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $footballCategory->id);
        $this->withClubToken($token)->getJson('/api/competitions')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.sport_id', $football->id);
        $this->withClubToken($token)->getJson('/api/positions')->assertForbidden();

        $this->withClubToken($token)->postJson('/api/seasons', [
            'name' => '2028', 'start_date' => '2028-01-01', 'end_date' => '2028-12-31', 'status' => 'planned',
        ])->assertForbidden();
        $this->withClubToken($token)->patchJson("/api/seasons/{$season->id}", ['name' => '2028'])->assertForbidden();
        $this->withClubToken($token)->deleteJson("/api/seasons/{$season->id}")->assertForbidden();

        $categoryPayload = [
            'sport_id' => $football->id,
            'season_id' => $season->id,
            'name' => 'Fútbol U17',
        ];
        $this->withClubToken($token)->postJson('/api/categories', $categoryPayload)->assertForbidden();
        $this->withClubToken($token)->patchJson("/api/categories/{$footballCategory->id}", ['name' => 'Nuevo nombre'])->assertForbidden();
        $this->withClubToken($token)->deleteJson("/api/categories/{$footballCategory->id}")->assertForbidden();

        $competitionPayload = [
            'sport_id' => $football->id,
            'season_id' => $season->id,
            'name' => 'Liga 2027',
        ];
        $this->withClubToken($token)->postJson('/api/competitions', $competitionPayload)->assertForbidden();
        $this->withClubToken($token)->patchJson("/api/competitions/{$footballCompetition->id}", ['name' => 'Nueva liga'])->assertForbidden();
        $this->withClubToken($token)->deleteJson("/api/competitions/{$footballCompetition->id}")->assertForbidden();

        $this->withClubToken($token)->getJson("/api/categories/{$basketballCategory->id}")->assertNotFound();
        $this->withClubToken($token)->getJson("/api/competitions/{$basketballCompetition->id}")->assertNotFound();
    }

    public function test_sport_coordinator_only_sees_categories_of_assigned_sports(): void
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

        // Jugadores y profes: solo los de fútbol.
        $this->withClubToken($token)->getJson('/api/players')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $footballPlayer->id);
        $this->withClubToken($token)->getJson("/api/players/{$footballPlayer->id}")->assertOk();
        $this->withClubToken($token)->getJson("/api/players/{$basketballPlayer->id}")->assertNotFound();
        $this->withClubToken($token)->getJson('/api/coaches')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $footballCoach->id);
        $this->withClubToken($token)->getJson("/api/coaches/{$basketballCoach->id}")->assertNotFound();
    }

    public function test_sport_coordinator_sees_matches_project_and_orders_only_of_their_sport(): void
    {
        $user = User::factory()->create();
        $token = $this->clubToken($this->tenantA(), $user, ['sport_coordinator']);

        $ids = $this->inTenant($this->tenantA(), function () use ($user) {
            $football = Sport::factory()->create(['name' => 'Fútbol']);
            $basketball = Sport::factory()->create(['name' => 'Básquet']);
            $season = Season::factory()->create(['name' => '2027', 'status' => 'active']);
            ClubUser::query()->where('global_user_id', $user->id)->sole()->sports()->attach($football->id);

            $footballCat = Category::factory()->create(['sport_id' => $football->id, 'season_id' => $season->id]);
            $basketballCat = Category::factory()->create(['sport_id' => $basketball->id, 'season_id' => $season->id]);
            $competition = Competition::factory()->create(['sport_id' => $football->id, 'season_id' => $season->id]);
            // Los pedidos los pide otra persona: los propios se ven siempre.
            $requester = ClubUser::create(['global_user_id' => User::factory()->create()->id, 'display_name' => 'Utilero', 'status' => 'active']);

            $standing = fn (Category $category, string $team) => CompetitionStanding::create([
                'competition_id' => $competition->id, 'category_id' => $category->id, 'position' => 1, 'team_name' => $team,
            ]);
            $standing($footballCat, 'Fútbol FC');
            $standing($basketballCat, 'Básquet BC');

            return [
                'competition' => $competition->id,
                'footballMatch' => GameMatch::factory()->create(['category_id' => $footballCat->id])->id,
                'basketballMatch' => GameMatch::factory()->create(['category_id' => $basketballCat->id])->id,
                'clubObjective' => SeasonObjective::create(['season_id' => $season->id, 'title' => 'Objetivo del club'])->id,
                'footballObjective' => SeasonObjective::create(['season_id' => $season->id, 'category_id' => $footballCat->id, 'title' => 'Ascender'])->id,
                'basketballObjective' => SeasonObjective::create(['season_id' => $season->id, 'category_id' => $basketballCat->id, 'title' => 'Playoffs'])->id,
                'footballOrder' => Order::factory()->create(['category_id' => $footballCat->id, 'requested_by' => $requester->id, 'status' => Order::STATUS_SUBMITTED])->id,
                'basketballOrder' => Order::factory()->create(['category_id' => $basketballCat->id, 'requested_by' => $requester->id, 'status' => Order::STATUS_SUBMITTED])->id,
            ];
        });

        $client = $this->withClubToken($token);

        $this->assertSame([$ids['footballMatch']], array_column($client->getJson('/api/matches')->assertOk()->json('data'), 'id'));
        $client->getJson("/api/matches/{$ids['footballMatch']}")->assertOk();
        $client->getJson("/api/matches/{$ids['basketballMatch']}")->assertNotFound();

        $objectives = array_column($client->getJson('/api/season-objectives')->assertOk()->json('data'), 'id');
        sort($objectives);
        $this->assertSame([$ids['clubObjective'], $ids['footballObjective']], $objectives);
        $client->getJson("/api/season-objectives/{$ids['basketballObjective']}")->assertNotFound();

        $this->assertSame(['Fútbol FC'], array_column(
            $client->getJson("/api/competitions/{$ids['competition']}/standings")->assertOk()->json('data'), 'team_name',
        ));

        $this->assertSame([$ids['footballOrder']], array_column($client->getJson('/api/orders')->assertOk()->json('data'), 'id'));
        // Los pedidos fuera de alcance responden 403 (OrderPolicy), sin datos.
        $client->getJson("/api/orders/{$ids['basketballOrder']}")->assertForbidden();

        // Solo consulta.
        $client->patchJson("/api/matches/{$ids['footballMatch']}", ['opponent' => 'Otro'])->assertForbidden();
        $client->postJson("/api/orders/{$ids['footballOrder']}/approve")->assertForbidden();
        $client->getJson('/api/club-users?team=1')->assertForbidden();
    }

    public function test_broadest_scope_wins_when_user_has_multiple_roles(): void
    {
        $user = User::factory()->create();
        // sport_coordinator gives sport scope, admin gives all scope
        $token = $this->clubToken($this->tenantA(), $user, ['sport_coordinator', 'admin']);

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

            // User is coach of Basketball, but sport coordinator of Football
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

        $this->withClubToken($token)->getJson('/api/players')->assertOk()->assertJsonCount(0, 'data');
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
