<?php

namespace Tests\Feature\Club;

use App\Models\Club\Category;
use App\Models\Club\ClubUser;
use App\Models\Club\Coach;
use App\Models\Club\Player;
use App\Models\Club\PlayerCategoryMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** ABM de jugadores (TIZ-15): los 10 casos de docs/guia-abm.md §11 y el alcance del profe. */
class PlayerApiTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    private function token(string $role = 'admin'): string
    {
        return $this->clubToken($this->tenantA(), roles: [$role]);
    }

    private function payload(array $overrides = []): array
    {
        return ['first_name' => 'Tomás', 'last_name' => 'Acosta', 'birth_date' => '2012-05-20', 'preferred_position' => 'Base', ...$overrides];
    }

    public function test_lists_paginated_with_filters(): void
    {
        $category = $this->inTenant($this->tenantA(), function () {
            Player::factory()->count(21)->sequence(fn ($s) => ['last_name' => 'Apellido '.str_pad((string) $s->index, 2, '0', STR_PAD_LEFT)])->create();
            $category = Category::factory()->create(['name' => 'U15']);
            $player = Player::factory()->create(['first_name' => 'Lucía', 'last_name' => 'Zapata', 'status' => 'inactive', 'registration_number' => 'FED-77']);
            PlayerCategoryMembership::create(['player_id' => $player->id, 'category_id' => $category->id, 'start_date' => '2026-03-01', 'shirt_number' => 7, 'status' => 'active']);

            return $category;
        });
        $token = $this->token();

        $this->withClubToken($token)->getJson('/api/players')
            ->assertOk()->assertJsonCount(20, 'data')->assertJsonPath('meta.total', 22)
            ->assertJsonPath('data.0.last_name', 'Apellido 00')
            ->assertJsonStructure(['data', 'links', 'meta']);

        $this->withClubToken($token)->getJson("/api/players?category_id={$category->id}")
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.memberships.0.category_name', 'U15')
            ->assertJsonPath('data.0.memberships.0.shirt_number', 7);
        $this->withClubToken($token)->getJson('/api/players?status=inactive')->assertJsonCount(1, 'data');
        $this->withClubToken($token)->getJson('/api/players?search=FED-77')->assertJsonCount(1, 'data');
        $this->withClubToken($token)->getJson('/api/players?sort=document_number')->assertUnprocessable();
    }

    public function test_shows_one_with_age(): void
    {
        $player = $this->inTenant($this->tenantA(), fn () => Player::factory()->create(['birth_date' => now()->subYears(14)->subDay()->toDateString()]));

        $this->withClubToken($this->token())->getJson("/api/players/{$player->id}")
            ->assertOk()->assertJsonPath('data.age', 14)->assertJsonPath('data.memberships', []);
    }

    public function test_creates_and_validates(): void
    {
        $token = $this->token();

        $this->withClubToken($token)->postJson('/api/players', $this->payload())
            ->assertCreated()->assertJsonPath('data.status', 'active')->assertJsonPath('data.birth_date', '2012-05-20');
        $this->assertTrue($this->inTenant($this->tenantA(), fn () => Player::where('last_name', 'Acosta')->exists()));

        $this->withClubToken($token)->postJson('/api/players', $this->payload(['birth_date' => null, 'dominant_side' => 'arriba', 'status' => 'retirado']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['birth_date' => 'La fecha de nacimiento es obligatoria.'])
            ->assertJsonValidationErrors(['dominant_side', 'status']);
        $this->withClubToken($token)->postJson('/api/players', $this->payload(['birth_date' => now()->addDay()->toDateString()]))
            ->assertUnprocessable()->assertJsonValidationErrors('birth_date');
    }

    public function test_updates_only_what_was_sent(): void
    {
        $player = $this->inTenant($this->tenantA(), fn () => Player::factory()->create($this->payload()));

        $this->withClubToken($this->token())->patchJson("/api/players/{$player->id}", ['preferred_position' => 'Escolta'])
            ->assertOk()->assertJsonPath('data.preferred_position', 'Escolta')->assertJsonPath('data.first_name', 'Tomás');
    }

    public function test_delete_is_soft_and_closes_memberships(): void
    {
        [$player, $membership] = $this->inTenant($this->tenantA(), function () {
            $player = Player::factory()->create();
            $membership = PlayerCategoryMembership::create(['player_id' => $player->id, 'category_id' => Category::factory()->create()->id, 'start_date' => '2026-03-01', 'status' => 'active']);

            return [$player, $membership];
        });
        $token = $this->token();

        $this->withClubToken($token)->deleteJson("/api/players/{$player->id}")->assertNoContent();
        $this->withClubToken($token)->getJson("/api/players/{$player->id}")->assertNotFound();

        $this->inTenant($this->tenantA(), function () use ($player, $membership) {
            $this->assertNotNull(Player::withTrashed()->find($player->id)->deleted_at);
            $this->assertSame('ended', $membership->refresh()->status);
        });
    }

    public function test_requires_token_and_permission(): void
    {
        $this->getJson('/api/players')->assertUnauthorized();

        $this->withClubToken($this->token('inventory_manager'))->getJson('/api/players')
            ->assertForbidden()->assertJsonPath('code', 'forbidden');
        $this->withClubToken($this->token('coach'))->postJson('/api/players', $this->payload())->assertForbidden();
    }

    public function test_missing_returns_404(): void
    {
        $this->withClubToken($this->token())->getJson('/api/players/999999')->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_a_player_of_another_club_is_not_visible(): void
    {
        $playerB = $this->inTenant($this->tenantB(), fn () => Player::factory()->create(['last_name' => 'Del club B']));
        $token = $this->token();

        $this->withClubToken($token)->getJson("/api/players/{$playerB->id}")->assertNotFound();
        $this->withClubToken($token)->patchJson("/api/players/{$playerB->id}", ['last_name' => 'X'])->assertNotFound();
        $this->withClubToken($token)->deleteJson("/api/players/{$playerB->id}")->assertNotFound();

        $this->assertSame('Del club B', $this->inTenant($this->tenantB(), fn () => Player::find($playerB->id)->last_name));
    }

    public function test_coach_only_sees_players_of_own_categories(): void
    {
        $user = User::factory()->create();
        $token = $this->clubToken($this->tenantA(), $user, ['coach']);

        [$mine, $other] = $this->inTenant($this->tenantA(), function () use ($user) {
            $clubUser = ClubUser::query()->where('global_user_id', $user->id)->sole();
            $coach = Coach::factory()->create(['club_user_id' => $clubUser->id]);
            $own = Category::factory()->create(['name' => 'U15']);
            $foreign = Category::factory()->create(['name' => 'U17']);
            DB::connection('tenant')->table('category_coaches')->insert(['category_id' => $own->id, 'coach_id' => $coach->id, 'assignment_type' => 'head_coach']);

            $mine = Player::factory()->create(['last_name' => 'Mío']);
            $other = Player::factory()->create(['last_name' => 'Ajeno']);
            Player::factory()->create(['last_name' => 'Sin categoría']);
            PlayerCategoryMembership::create(['player_id' => $mine->id, 'category_id' => $own->id, 'start_date' => '2026-03-01', 'status' => 'active']);
            PlayerCategoryMembership::create(['player_id' => $other->id, 'category_id' => $foreign->id, 'start_date' => '2026-03-01', 'status' => 'active']);

            return [$mine, $other];
        });

        $this->withClubToken($token)->getJson('/api/players')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.last_name', 'Mío');
        $this->withClubToken($token)->getJson("/api/players/{$mine->id}")->assertOk();
        $this->withClubToken($token)->getJson("/api/players/{$other->id}")->assertNotFound();

        $this->withClubToken($this->token('coordinator'))->getJson('/api/players')->assertJsonCount(3, 'data');
    }

    public function test_history_lists_current_and_ended_categories(): void
    {
        $player = $this->inTenant($this->tenantA(), function () {
            $player = Player::factory()->create();
            $u13 = Category::factory()->create(['name' => 'U13']);
            $u15 = Category::factory()->create(['name' => 'U15']);
            PlayerCategoryMembership::create(['player_id' => $player->id, 'category_id' => $u13->id, 'start_date' => '2025-03-01', 'end_date' => '2026-02-28', 'status' => 'ended']);
            PlayerCategoryMembership::create(['player_id' => $player->id, 'category_id' => $u15->id, 'start_date' => '2026-03-01', 'shirt_number' => 9, 'status' => 'active']);

            return $player;
        });

        $this->withClubToken($this->token())->getJson("/api/players/{$player->id}/memberships")
            ->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.category_name', 'U15')
            ->assertJsonPath('data.0.status', 'active')
            ->assertJsonPath('data.1.category_name', 'U13')
            ->assertJsonPath('data.1.end_date', '2026-02-28');

        $this->withClubToken($this->token('inventory_manager'))->getJson("/api/players/{$player->id}/memberships")->assertForbidden();
        $this->withClubToken($this->token())->getJson('/api/players/999999/memberships')->assertNotFound();
    }
}
