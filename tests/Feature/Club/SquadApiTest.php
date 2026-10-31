<?php

namespace Tests\Feature\Club;

use App\Models\Club\Category;
use App\Models\Club\ClubUser;
use App\Models\Club\Coach;
use App\Models\Club\Player;
use App\Models\Club\PlayerCategoryMembership;
use App\Models\Club\Season;
use App\Models\Club\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** Plantel por categoría y profes por categoría (TIZ-15). */
class SquadApiTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    private function token(string $role = 'admin'): string
    {
        return $this->clubToken($this->tenantA(), roles: [$role]);
    }

    /** @return array{u15: Category, u17: Category, voley: Category, player: Player} */
    private function setUpSquad(): array
    {
        return $this->inTenant($this->tenantA(), function () {
            $season = Season::factory()->create(['name' => 'Temporada 2026']);
            $basquet = Sport::factory()->create(['name' => 'Básquet']);
            $voley = Sport::factory()->create(['name' => 'Vóley']);

            return [
                'u15' => Category::factory()->create(['sport_id' => $basquet->id, 'season_id' => $season->id, 'name' => 'Sub-15']),
                'u17' => Category::factory()->create(['sport_id' => $basquet->id, 'season_id' => $season->id, 'name' => 'Sub-17']),
                'voley' => Category::factory()->create(['sport_id' => $voley->id, 'season_id' => $season->id, 'name' => 'Vóley Sub-15']),
                'player' => Player::factory()->create(['first_name' => 'Tomás', 'last_name' => 'Acosta']),
            ];
        });
    }

    public function test_player_moves_from_u15_to_u17_keeping_history(): void
    {
        ['u15' => $u15, 'u17' => $u17, 'player' => $player] = $this->setUpSquad();
        $token = $this->token();

        $first = $this->withClubToken($token)
            ->postJson("/api/categories/{$u15->id}/memberships", ['player_id' => $player->id, 'start_date' => '2026-03-01', 'shirt_number' => 10])
            ->assertCreated()
            ->assertJsonPath('data.category_name', 'Sub-15')
            ->assertJsonPath('data.player_name', 'Tomás Acosta')
            ->assertJsonPath('data.status', 'active')
            ->json('data.id');

        // Mismo deporte: sin close_previous, no lo deja jugar en dos categorías.
        $this->withClubToken($token)
            ->postJson("/api/categories/{$u17->id}/memberships", ['player_id' => $player->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('player_id');

        $this->withClubToken($token)
            ->postJson("/api/categories/{$u17->id}/memberships", ['player_id' => $player->id, 'start_date' => '2026-07-01', 'close_previous' => true])
            ->assertCreated();

        $this->withClubToken($token)->getJson("/api/categories/{$u15->id}/memberships")->assertJsonCount(0, 'data');
        $this->withClubToken($token)->getJson("/api/categories/{$u15->id}/memberships?status=ended")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $first)
            ->assertJsonPath('data.0.end_date', '2026-07-01');
        $this->withClubToken($token)->getJson("/api/players/{$player->id}")
            ->assertJsonCount(1, 'data.memberships')
            ->assertJsonPath('data.memberships.0.category_name', 'Sub-17');
    }

    public function test_a_player_can_be_in_categories_of_different_sports(): void
    {
        ['u15' => $u15, 'voley' => $voley, 'player' => $player] = $this->setUpSquad();
        $token = $this->token();

        $this->withClubToken($token)->postJson("/api/categories/{$u15->id}/memberships", ['player_id' => $player->id])->assertCreated();
        $this->withClubToken($token)->postJson("/api/categories/{$voley->id}/memberships", ['player_id' => $player->id])->assertCreated();
        $this->withClubToken($token)->postJson("/api/categories/{$u15->id}/memberships", ['player_id' => $player->id])
            ->assertUnprocessable()->assertJsonValidationErrors(['player_id' => 'El jugador ya está en esta categoría.']);
    }

    public function test_shirt_numbers_do_not_repeat_among_active_players(): void
    {
        ['u15' => $u15, 'player' => $player] = $this->setUpSquad();
        $other = $this->inTenant($this->tenantA(), fn () => Player::factory()->create());
        $token = $this->token();

        $membership = $this->withClubToken($token)->postJson("/api/categories/{$u15->id}/memberships", ['player_id' => $player->id, 'shirt_number' => 10])->json('data.id');
        $this->withClubToken($token)->postJson("/api/categories/{$u15->id}/memberships", ['player_id' => $other->id, 'shirt_number' => 10])
            ->assertUnprocessable()->assertJsonValidationErrors(['shirt_number' => 'El número 10 ya lo usa otro jugador de la categoría.']);

        $this->withClubToken($token)->patchJson("/api/memberships/{$membership}", ['shirt_number' => 5])->assertOk()->assertJsonPath('data.shirt_number', 5);

        // Al cerrar la asignación, el número queda libre.
        $this->withClubToken($token)->postJson("/api/memberships/{$membership}/end")->assertOk()->assertJsonPath('data.status', 'ended');
        $this->withClubToken($token)->postJson("/api/memberships/{$membership}/end")->assertUnprocessable();
        $this->withClubToken($token)->postJson("/api/categories/{$u15->id}/memberships", ['player_id' => $other->id, 'shirt_number' => 5])->assertCreated();
    }

    public function test_assigns_and_ends_coaches_of_a_category(): void
    {
        ['u15' => $u15] = $this->setUpSquad();
        $coach = $this->inTenant($this->tenantA(), fn () => Coach::factory()->create(['first_name' => 'Laura', 'last_name' => 'Gómez']));
        $token = $this->token();

        $assignment = $this->withClubToken($token)
            ->postJson("/api/categories/{$u15->id}/coaches", ['coach_id' => $coach->id, 'assignment_type' => 'head_coach', 'start_date' => '2026-03-01'])
            ->assertCreated()
            ->assertJsonPath('data.coach_name', 'Laura Gómez')
            ->json('data.id');

        $this->withClubToken($token)->postJson("/api/categories/{$u15->id}/coaches", ['coach_id' => $coach->id, 'assignment_type' => 'head_coach'])
            ->assertUnprocessable()->assertJsonValidationErrors('coach_id');
        $this->withClubToken($token)->postJson("/api/categories/{$u15->id}/coaches", ['coach_id' => $coach->id, 'assignment_type' => 'utilero'])
            ->assertUnprocessable()->assertJsonValidationErrors('assignment_type');

        $this->withClubToken($token)->getJson("/api/categories/{$u15->id}/coaches")->assertJsonCount(1, 'data');
        $this->withClubToken($token)->postJson("/api/category-coaches/{$assignment}/end", ['end_date' => '2026-02-01'])
            ->assertUnprocessable()->assertJsonValidationErrors('end_date');
        $this->withClubToken($token)->postJson("/api/category-coaches/{$assignment}/end", ['end_date' => now()->subDay()->toDateString()])->assertOk();
        $this->withClubToken($token)->getJson("/api/categories/{$u15->id}/coaches")->assertJsonCount(0, 'data');
        $this->withClubToken($token)->getJson("/api/categories/{$u15->id}/coaches?status=all")->assertJsonCount(1, 'data');
    }

    public function test_a_new_assignment_gives_the_coach_access_to_the_category(): void
    {
        ['u15' => $u15, 'player' => $player] = $this->setUpSquad();
        $user = User::factory()->create();
        $coachToken = $this->clubToken($this->tenantA(), $user, ['coach']);
        $coach = $this->inTenant($this->tenantA(), function () use ($user, $player, $u15) {
            PlayerCategoryMembership::create(['player_id' => $player->id, 'category_id' => $u15->id, 'start_date' => '2026-03-01', 'status' => 'active']);

            return Coach::factory()->create(['club_user_id' => ClubUser::query()->where('global_user_id', $user->id)->sole()->id]);
        });

        $this->withClubToken($coachToken)->getJson("/api/categories/{$u15->id}/memberships")->assertNotFound();

        $this->withClubToken($this->token('coordinator'))
            ->postJson("/api/categories/{$u15->id}/coaches", ['coach_id' => $coach->id, 'assignment_type' => 'head_coach'])
            ->assertCreated();

        $this->withClubToken($coachToken)->getJson("/api/categories/{$u15->id}/memberships")->assertOk()->assertJsonCount(1, 'data');
        // El profe ve el plantel, pero no lo arma.
        $this->withClubToken($coachToken)->postJson("/api/categories/{$u15->id}/memberships", ['player_id' => $player->id])->assertForbidden();
    }

    public function test_requires_permissions(): void
    {
        ['u15' => $u15, 'player' => $player] = $this->setUpSquad();
        $coach = $this->inTenant($this->tenantA(), fn () => Coach::factory()->create());

        $this->getJson("/api/categories/{$u15->id}/memberships")->assertUnauthorized();
        $token = $this->token('inventory_manager');
        $this->withClubToken($token)->getJson("/api/categories/{$u15->id}/memberships")->assertForbidden();
        $this->withClubToken($token)->postJson("/api/categories/{$u15->id}/memberships", ['player_id' => $player->id])->assertForbidden();
        $this->withClubToken($this->token('coach'))->postJson("/api/categories/{$u15->id}/coaches", ['coach_id' => $coach->id, 'assignment_type' => 'assistant'])->assertForbidden();
    }

    public function test_squads_are_isolated_between_clubs(): void
    {
        ['u15' => $u15] = $this->setUpSquad();
        $playerB = $this->inTenant($this->tenantB(), fn () => Player::factory()->create());

        $this->withClubToken($this->clubToken($this->tenantB(), roles: ['admin']))
            ->getJson("/api/categories/{$u15->id}/memberships")->assertNotFound();
        $this->withClubToken($this->token())
            ->postJson("/api/categories/{$u15->id}/memberships", ['player_id' => $playerB->id + 1000])
            ->assertUnprocessable()->assertJsonValidationErrors('player_id');
    }
}
