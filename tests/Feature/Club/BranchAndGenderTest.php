<?php

namespace Tests\Feature\Club;

use App\Models\Club\Category;
use App\Models\Club\Player;
use App\Models\Club\PlayerCategoryMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** Rama de las categorías y género de los jugadores (TIZ-40). */
class BranchAndGenderTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    private function token(): string
    {
        return $this->clubToken($this->tenantA(), roles: ['admin']);
    }

    public function test_categories_and_players_save_and_filter_branch_and_gender(): void
    {
        $base = $this->inTenant($this->tenantA(), fn () => Category::factory()->create());
        $payload = ['sport_id' => $base->sport_id, 'season_id' => $base->season_id];

        $this->withClubToken($this->token())->postJson('/api/categories', [...$payload, 'name' => 'Sub 21 femenino', 'branch' => 'female'])
            ->assertCreated()->assertJsonPath('data.branch', 'female');
        $this->withClubToken($this->token())->postJson('/api/categories', [...$payload, 'name' => 'Sub 21 masculino', 'branch' => 'male'])
            ->assertCreated();
        $this->withClubToken($this->token())->postJson('/api/categories', [...$payload, 'name' => 'Sub 21', 'branch' => 'women'])
            ->assertUnprocessable()->assertJsonValidationErrors('branch');

        $this->withClubToken($this->token())->getJson('/api/categories?branch=female')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Sub 21 femenino');
        $this->withClubToken($this->token())->getJson("/api/categories/{$base->id}")->assertJsonPath('data.branch', null);

        $this->withClubToken($this->token())->postJson('/api/players', ['first_name' => 'Ana', 'last_name' => 'Acosta', 'birth_date' => '2006-05-01', 'gender' => 'female'])
            ->assertCreated()->assertJsonPath('data.gender', 'female');
        $this->withClubToken($this->token())->postJson('/api/players', ['first_name' => 'Juan', 'last_name' => 'Pérez', 'birth_date' => '2006-05-01'])
            ->assertCreated()->assertJsonPath('data.gender', null);
        $this->withClubToken($this->token())->getJson('/api/players?gender=female')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.first_name', 'Ana');
    }

    public function test_a_player_cannot_join_the_opposite_branch(): void
    {
        [$female, $mixed, $noBranch, $juan, $sinDato] = $this->inTenant($this->tenantA(), fn () => [
            Category::factory()->create(['name' => 'Sub 21 femenino', 'branch' => 'female']),
            Category::factory()->create(['name' => 'Sub 21 mixto', 'branch' => 'mixed']),
            Category::factory()->create(['name' => 'Escuelita']),
            Player::factory()->create(['first_name' => 'Juan', 'last_name' => 'Pérez', 'gender' => 'male']),
            Player::factory()->create(['gender' => null]),
        ]);

        $this->withClubToken($this->token())->postJson("/api/categories/{$female->id}/memberships", ['player_id' => $juan->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['player_id' => 'Juan Pérez no puede sumarse a Sub 21 femenino: la categoría es de rama femenina.']);
        $this->withClubToken($this->token())->postJson("/api/categories/{$mixed->id}/memberships", ['player_id' => $juan->id])->assertCreated();
        $this->withClubToken($this->token())->postJson("/api/categories/{$female->id}/memberships", ['player_id' => $sinDato->id])->assertCreated();
        $this->withClubToken($this->token())->postJson("/api/categories/{$noBranch->id}/memberships", ['player_id' => $sinDato->id])->assertCreated();
    }

    public function test_editing_cannot_contradict_the_active_squad(): void
    {
        [$category, $ana] = $this->inTenant($this->tenantA(), function () {
            $category = Category::factory()->create(['name' => 'Sub 21', 'branch' => null]);
            $ana = Player::factory()->create(['first_name' => 'Ana', 'last_name' => 'Acosta', 'gender' => 'female']);
            PlayerCategoryMembership::create(['player_id' => $ana->id, 'category_id' => $category->id, 'start_date' => '2026-03-01', 'status' => 'active']);

            return [$category, $ana];
        });

        // La categoría no pasa a masculina con Ana en el plantel; a femenina o mixta, sí.
        $this->withClubToken($this->token())->patchJson("/api/categories/{$category->id}", ['branch' => 'male'])
            ->assertUnprocessable()->assertJsonValidationErrors('branch');
        $this->withClubToken($this->token())->patchJson("/api/categories/{$category->id}", ['branch' => 'female'])
            ->assertOk()->assertJsonPath('data.branch', 'female');

        // Ana no pasa a `male` estando en una categoría femenina; a `other`, sí.
        $this->withClubToken($this->token())->patchJson("/api/players/{$ana->id}", ['gender' => 'male'])
            ->assertUnprocessable()->assertJsonValidationErrors('gender');
        $this->withClubToken($this->token())->patchJson("/api/players/{$ana->id}", ['gender' => 'other'])
            ->assertOk()->assertJsonPath('data.gender', 'other');
    }
}
