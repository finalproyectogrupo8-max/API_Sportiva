<?php

namespace Tests\Feature\Club;

use App\Models\Club\Category;
use App\Models\Club\Season;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** ABM de temporadas (club-catalog-crud): los 10 casos de docs/guia-abm.md §11. */
class SeasonApiTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    private function token(string $role = 'admin'): string
    {
        return $this->clubToken($this->tenantA(), roles: [$role]);
    }

    private function payload(array $overrides = []): array
    {
        return ['name' => 'Temporada 2027', 'start_date' => '2027-03-01', 'end_date' => '2027-12-15', 'status' => 'planned', ...$overrides];
    }

    public function test_lists_paginated_with_filters(): void
    {
        $this->inTenant($this->tenantA(), function () {
            Season::factory()->count(21)->sequence(fn ($s) => ['name' => 'Temporada '.(2000 + $s->index), 'start_date' => (2000 + $s->index).'-01-01', 'end_date' => (2000 + $s->index).'-12-31'])->create();
            Season::factory()->create(['name' => 'Actual', 'status' => 'active', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        });
        $token = $this->token();

        $this->withClubToken($token)->getJson('/api/seasons')
            ->assertOk()->assertJsonCount(20, 'data')->assertJsonPath('meta.total', 22)->assertJsonStructure(['data', 'links', 'meta']);
        $this->withClubToken($token)->getJson('/api/seasons?status=active')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Actual');
        $this->withClubToken($token)->getJson('/api/seasons?sort=-start_date&per_page=1')
            ->assertOk()->assertJsonPath('data.0.name', 'Actual');
        $this->withClubToken($token)->getJson('/api/seasons?status=archived')->assertUnprocessable();
    }

    public function test_shows_one(): void
    {
        $season = $this->inTenant($this->tenantA(), fn () => Season::factory()->create($this->payload()));

        $this->withClubToken($this->token())->getJson("/api/seasons/{$season->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Temporada 2027')
            ->assertJsonPath('data.start_date', '2027-03-01')
            ->assertJsonPath('data.end_date', '2027-12-15');
    }

    public function test_creates_and_validates(): void
    {
        $token = $this->token();

        $this->withClubToken($token)->postJson('/api/seasons', $this->payload())
            ->assertCreated()->assertJsonPath('data.status', 'planned');
        $this->assertTrue($this->inTenant($this->tenantA(), fn () => Season::where('name', 'Temporada 2027')->exists()));

        $this->withClubToken($token)->postJson('/api/seasons', $this->payload(['end_date' => '2027-01-01', 'status' => 'open']))
            ->assertUnprocessable()->assertJsonValidationErrors(['end_date', 'status']);
    }

    public function test_updates_only_what_was_sent(): void
    {
        $season = $this->inTenant($this->tenantA(), fn () => Season::factory()->create($this->payload()));

        $this->withClubToken($this->token())->patchJson("/api/seasons/{$season->id}", ['status' => 'active'])
            ->assertOk()->assertJsonPath('data.status', 'active')->assertJsonPath('data.name', 'Temporada 2027');
    }

    public function test_deletes_and_conflicts_when_in_use(): void
    {
        [$free, $used] = $this->inTenant($this->tenantA(), function () {
            $used = Season::factory()->create(['name' => 'Con categorías']);
            Category::factory()->create(['season_id' => $used->id, 'name' => 'U17', 'is_active' => false]);

            return [Season::factory()->create(['name' => 'Vacía']), $used];
        });
        $token = $this->token();

        $this->withClubToken($token)->deleteJson("/api/seasons/{$free->id}")->assertNoContent();
        $this->assertNull($this->inTenant($this->tenantA(), fn () => Season::find($free->id)));

        // Aunque la categoría esté inactiva, la temporada sigue en uso.
        $this->withClubToken($token)->deleteJson("/api/seasons/{$used->id}")
            ->assertConflict()->assertJsonPath('code', 'resource_in_use');
    }

    public function test_requires_token_and_permission(): void
    {
        $this->getJson('/api/seasons')->assertUnauthorized();

        $token = $this->token('maintenance_manager');
        $this->withClubToken($token)->getJson('/api/seasons')->assertForbidden()->assertJsonPath('code', 'forbidden');
        $this->withClubToken($token)->postJson('/api/seasons', $this->payload())->assertForbidden();
    }

    public function test_missing_returns_404(): void
    {
        $this->withClubToken($this->token())->getJson('/api/seasons/999999')->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_a_season_of_another_club_is_not_visible(): void
    {
        $seasonB = $this->inTenant($this->tenantB(), fn () => Season::factory()->create(['name' => 'Del club B']));
        $token = $this->token();

        $this->withClubToken($token)->getJson("/api/seasons/{$seasonB->id}")->assertNotFound();
        $this->withClubToken($token)->patchJson("/api/seasons/{$seasonB->id}", ['name' => 'X'])->assertNotFound();
        $this->withClubToken($token)->deleteJson("/api/seasons/{$seasonB->id}")->assertNotFound();

        $this->assertSame('Del club B', $this->inTenant($this->tenantB(), fn () => Season::find($seasonB->id)->name));
    }
}
