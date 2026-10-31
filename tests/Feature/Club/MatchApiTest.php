<?php

namespace Tests\Feature\Club;

use App\Models\Club\Category;
use App\Models\Club\GameMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** Partidos (TIZ-16): los 10 casos de docs/guia-abm.md §11 y la carga del resultado. */
class MatchApiTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    private function token(string $role = 'admin'): string
    {
        return $this->clubToken($this->tenantA(), roles: [$role]);
    }

    private function payload(Category $category, array $overrides = []): array
    {
        return [
            'category_id' => $category->id,
            'opponent' => 'Club Atlético Norte',
            'match_date' => '2026-11-07 10:00:00',
            'meet_at' => '2026-11-07 09:15:00',
            'venue' => 'Estadio cubierto',
            'maps_url' => 'https://maps.google.com/?q=estadio',
            'is_home' => true,
            'uniform' => 'home',
            'uniform_color' => 'Azul',
            'what_to_bring' => 'Botella de agua',
            ...$overrides,
        ];
    }

    public function test_lists_paginated_with_filters(): void
    {
        [$u15, $u17] = $this->inTenant($this->tenantA(), function () {
            $u15 = Category::factory()->create(['name' => 'U15']);
            $u17 = Category::factory()->create(['name' => 'U17']);
            GameMatch::factory()->count(21)->create(['category_id' => $u15->id, 'match_date' => '2026-11-10 10:00:00']);
            GameMatch::factory()->create(['category_id' => $u17->id, 'match_date' => '2026-12-05 10:00:00', 'status' => 'postponed']);

            return [$u15, $u17];
        });
        $token = $this->token();

        $this->withClubToken($token)->getJson('/api/matches')
            ->assertOk()->assertJsonCount(20, 'data')->assertJsonPath('meta.total', 22)->assertJsonStructure(['data', 'links', 'meta']);
        $this->withClubToken($token)->getJson("/api/matches?category_id={$u17->id}")
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.category_name', 'U17');
        $this->withClubToken($token)->getJson('/api/matches?from=2026-12-01&to=2026-12-31')->assertJsonCount(1, 'data');
        $this->withClubToken($token)->getJson('/api/matches?status=postponed')->assertJsonCount(1, 'data');
        $this->withClubToken($token)->getJson('/api/matches?from=2026-12-01&to=2026-11-01')->assertUnprocessable();
    }

    public function test_creates_shows_and_validates(): void
    {
        $category = $this->inTenant($this->tenantA(), fn () => Category::factory()->create());
        $token = $this->token();

        $id = $this->withClubToken($token)->postJson('/api/matches', $this->payload($category))
            ->assertCreated()
            ->assertJsonPath('data.status', 'scheduled')
            ->assertJsonPath('data.uniform', 'home')
            ->assertJsonPath('data.what_to_bring', 'Botella de agua')
            ->json('data.id');
        $this->withClubToken($token)->getJson("/api/matches/{$id}")->assertOk()->assertJsonPath('data.opponent', 'Club Atlético Norte');

        $this->withClubToken($token)->postJson('/api/matches', $this->payload($category, ['meet_at' => '2026-11-07 11:00:00', 'uniform' => 'violeta', 'maps_url' => 'no es url']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['meet_at' => 'La hora de presentación tiene que ser antes del partido.'])
            ->assertJsonValidationErrors(['uniform', 'maps_url']);
        $this->withClubToken($token)->postJson('/api/matches', ['opponent' => 'X'])
            ->assertUnprocessable()->assertJsonValidationErrors(['category_id', 'match_date', 'is_home']);
    }

    public function test_loading_the_result_marks_it_played(): void
    {
        $match = $this->inTenant($this->tenantA(), fn () => GameMatch::factory()->create(['opponent' => 'Rival']));

        $this->withClubToken($this->token())->patchJson("/api/matches/{$match->id}", ['our_score' => 61, 'opponent_score' => 58])
            ->assertOk()
            ->assertJsonPath('data.status', 'played')
            ->assertJsonPath('data.our_score', 61)
            ->assertJsonPath('data.opponent', 'Rival');
    }

    public function test_delete_is_soft(): void
    {
        $match = $this->inTenant($this->tenantA(), fn () => GameMatch::factory()->create());
        $token = $this->token();

        $this->withClubToken($token)->deleteJson("/api/matches/{$match->id}")->assertNoContent();
        $this->withClubToken($token)->getJson("/api/matches/{$match->id}")->assertNotFound();
        $this->assertNotNull($this->inTenant($this->tenantA(), fn () => GameMatch::withTrashed()->find($match->id)->deleted_at));
    }

    public function test_requires_token_and_permission(): void
    {
        $category = $this->inTenant($this->tenantA(), fn () => Category::factory()->create());
        $this->getJson('/api/matches')->assertUnauthorized();

        // El utilero ve el fixture pero no lo carga.
        $this->withClubToken($this->token('inventory_manager'))->getJson('/api/matches')->assertOk();
        $this->withClubToken($this->token('inventory_manager'))->postJson('/api/matches', $this->payload($category))
            ->assertForbidden()->assertJsonPath('code', 'forbidden');
    }

    public function test_missing_returns_404(): void
    {
        $this->withClubToken($this->token())->getJson('/api/matches/999999')->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_a_match_of_another_club_is_not_visible(): void
    {
        $matchB = $this->inTenant($this->tenantB(), fn () => GameMatch::factory()->count(2)->create()->last());
        $token = $this->token();

        $this->withClubToken($token)->getJson("/api/matches/{$matchB->id}")->assertNotFound();
        $this->withClubToken($token)->patchJson("/api/matches/{$matchB->id}", ['opponent' => 'X'])->assertNotFound();
        $this->withClubToken($token)->deleteJson("/api/matches/{$matchB->id}")->assertNotFound();
    }
}
