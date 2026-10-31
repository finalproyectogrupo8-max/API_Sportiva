<?php

namespace Tests\Feature\Club;

use App\Models\Club\Category;
use App\Models\Club\Season;
use App\Models\Club\Sport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** ABM de categorías (club-catalog-crud): los 10 casos de docs/guia-abm.md §11. */
class CategoryApiTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    private function token(string $role = 'admin'): string
    {
        return $this->clubToken($this->tenantA(), roles: [$role]);
    }

    /** @return array{0: Sport, 1: Season} */
    private function catalog(): array
    {
        return $this->inTenant($this->tenantA(), fn () => [
            Sport::factory()->create(['name' => 'Básquet']),
            Season::factory()->create(['name' => 'Temporada 2027']),
        ]);
    }

    public function test_lists_paginated_with_filters(): void
    {
        [$sport, $season] = $this->catalog();
        $this->inTenant($this->tenantA(), function () use ($sport, $season) {
            Category::factory()->count(21)->sequence(fn ($s) => ['name' => 'Cat '.$s->index])->create(['sport_id' => $sport->id, 'season_id' => $season->id]);
            Category::factory()->create(['name' => 'Primera', 'is_active' => false]);
        });
        $token = $this->token();

        $this->withClubToken($token)->getJson('/api/categories')
            ->assertOk()->assertJsonCount(20, 'data')->assertJsonPath('meta.total', 22)->assertJsonStructure(['data', 'links', 'meta']);
        $this->withClubToken($token)->getJson("/api/categories?sport_id={$sport->id}&season_id={$season->id}&per_page=100")
            ->assertOk()->assertJsonCount(21, 'data');
        $this->withClubToken($token)->getJson('/api/categories?is_active=0')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Primera');
    }

    public function test_shows_one(): void
    {
        [$sport, $season] = $this->catalog();
        $category = $this->inTenant($this->tenantA(), fn () => Category::factory()->create(['sport_id' => $sport->id, 'season_id' => $season->id, 'name' => 'U15', 'min_age' => 13, 'max_age' => 15]));

        $this->withClubToken($this->token())->getJson("/api/categories/{$category->id}")
            ->assertOk()
            ->assertJsonPath('data.sport_id', $sport->id)
            ->assertJsonPath('data.min_age', 13)
            ->assertJsonPath('data.max_age', 15);
    }

    public function test_creates_and_validates(): void
    {
        [$sport, $season] = $this->catalog();
        $token = $this->token();
        $payload = ['sport_id' => $sport->id, 'season_id' => $season->id, 'name' => 'U17', 'min_age' => 15, 'max_age' => 17];

        $this->withClubToken($token)->postJson('/api/categories', $payload)
            ->assertCreated()->assertJsonPath('data.is_active', true);

        // Mismo nombre en el mismo deporte y temporada.
        $this->withClubToken($token)->postJson('/api/categories', $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('name');

        $this->withClubToken($token)->postJson('/api/categories', [...$payload, 'name' => 'U19', 'sport_id' => 999999, 'min_age' => 19, 'max_age' => 17])
            ->assertUnprocessable()->assertJsonValidationErrors(['sport_id', 'max_age']);
    }

    public function test_updates_only_what_was_sent(): void
    {
        [$sport, $season] = $this->catalog();
        $category = $this->inTenant($this->tenantA(), fn () => Category::factory()->create(['sport_id' => $sport->id, 'season_id' => $season->id, 'name' => 'U13', 'min_age' => 11, 'max_age' => 13]));
        $token = $this->token();

        $this->withClubToken($token)->patchJson("/api/categories/{$category->id}", ['name' => 'Sub-13'])
            ->assertOk()->assertJsonPath('data.name', 'Sub-13')->assertJsonPath('data.min_age', 11);

        // La edad máxima se compara con la mínima guardada.
        $this->withClubToken($token)->patchJson("/api/categories/{$category->id}", ['max_age' => 9])
            ->assertUnprocessable()->assertJsonValidationErrors('max_age');
    }

    public function test_delete_deactivates(): void
    {
        $category = $this->inTenant($this->tenantA(), fn () => Category::factory()->create(['name' => 'U11']));

        $this->withClubToken($this->token())->deleteJson("/api/categories/{$category->id}")->assertNoContent();
        $this->assertFalse($this->inTenant($this->tenantA(), fn () => Category::find($category->id)->is_active));
    }

    public function test_requires_token_and_permission(): void
    {
        $category = $this->inTenant($this->tenantA(), fn () => Category::factory()->create(['name' => 'U11']));
        $this->getJson('/api/categories')->assertUnauthorized();

        // El profe ve categorías, pero no las edita.
        $token = $this->token('coach');
        $this->withClubToken($token)->getJson('/api/categories')->assertOk();
        $this->withClubToken($token)->patchJson("/api/categories/{$category->id}", ['name' => 'X'])
            ->assertForbidden()->assertJsonPath('code', 'forbidden');
        $this->withClubToken($this->token('inventory_manager'))->getJson('/api/categories')->assertForbidden();
    }

    public function test_missing_returns_404(): void
    {
        $this->withClubToken($this->token())->getJson('/api/categories/999999')->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_a_category_of_another_club_is_not_visible(): void
    {
        $categoryB = $this->inTenant($this->tenantB(), fn () => Category::factory()->create(['name' => 'Del club B']));
        $token = $this->token();

        $this->withClubToken($token)->getJson("/api/categories/{$categoryB->id}")->assertNotFound();
        $this->withClubToken($token)->patchJson("/api/categories/{$categoryB->id}", ['name' => 'X'])->assertNotFound();
        $this->withClubToken($token)->deleteJson("/api/categories/{$categoryB->id}")->assertNotFound();

        $this->assertTrue($this->inTenant($this->tenantB(), fn () => Category::find($categoryB->id)->is_active));
    }
}
