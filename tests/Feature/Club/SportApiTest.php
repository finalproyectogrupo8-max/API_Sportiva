<?php

namespace Tests\Feature\Club;

use App\Models\Club\Category;
use App\Models\Club\Sport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** ABM de deportes (club-catalog-crud): los 10 casos de docs/guia-abm.md §11. */
class SportApiTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    private function token(string $role = 'admin'): string
    {
        return $this->clubToken($this->tenantA(), roles: [$role]);
    }

    public function test_lists_paginated_with_filters(): void
    {
        $this->inTenant($this->tenantA(), function () {
            Sport::factory()->count(22)->sequence(fn ($s) => ['name' => 'Deporte '.str_pad((string) $s->index, 2, '0', STR_PAD_LEFT)])->create();
            Sport::factory()->create(['name' => 'Vóley', 'is_active' => false]);
        });
        $token = $this->token();

        $this->withClubToken($token)->getJson('/api/sports')
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.total', 23)
            ->assertJsonStructure(['data', 'links', 'meta']);

        $this->withClubToken($token)->getJson('/api/sports?is_active=0')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Vóley');

        $this->withClubToken($token)->getJson('/api/sports?search=Deporte 0&sort=-name')
            ->assertOk()->assertJsonCount(10, 'data')->assertJsonPath('data.0.name', 'Deporte 09');

        $this->withClubToken($token)->getJson('/api/sports?sort=password')->assertUnprocessable();
    }

    public function test_shows_one(): void
    {
        $sport = $this->inTenant($this->tenantA(), fn () => Sport::factory()->create(['name' => 'Básquet']));

        $this->withClubToken($this->token())->getJson("/api/sports/{$sport->id}")
            ->assertOk()
            ->assertJsonPath('data', fn (array $data) => $data['name'] === 'Básquet' && $data['is_active'] === true);
    }

    public function test_creates_and_validates(): void
    {
        $token = $this->token();

        $this->withClubToken($token)->postJson('/api/sports', ['name' => 'Hockey'])
            ->assertCreated()->assertJsonPath('data.is_active', true);
        $this->assertTrue($this->inTenant($this->tenantA(), fn () => Sport::where('name', 'Hockey')->exists()));

        $this->withClubToken($token)->postJson('/api/sports', ['name' => 'Hockey'])
            ->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->withClubToken($token)->postJson('/api/sports', ['name' => ''])
            ->assertUnprocessable()->assertJsonValidationErrors('name');
    }

    public function test_updates_only_what_was_sent(): void
    {
        $sport = $this->inTenant($this->tenantA(), fn () => Sport::factory()->create(['name' => 'Futsal', 'is_active' => true]));

        $this->withClubToken($this->token())->patchJson("/api/sports/{$sport->id}", ['name' => 'Fútbol sala'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Fútbol sala')
            ->assertJsonPath('data.is_active', true);
    }

    public function test_delete_deactivates_and_conflicts_when_in_use(): void
    {
        [$free, $used] = $this->inTenant($this->tenantA(), function () {
            $used = Sport::factory()->create(['name' => 'Básquet']);
            Category::factory()->create(['sport_id' => $used->id, 'name' => 'U15']);

            return [Sport::factory()->create(['name' => 'Golf']), $used];
        });
        $token = $this->token();

        $this->withClubToken($token)->deleteJson("/api/sports/{$free->id}")->assertNoContent();
        $this->assertFalse($this->inTenant($this->tenantA(), fn () => Sport::find($free->id)->is_active));

        // D11: un deporte con categorías activas no se da de baja.
        $this->withClubToken($token)->deleteJson("/api/sports/{$used->id}")
            ->assertConflict()->assertJsonPath('code', 'resource_in_use');
        $this->assertTrue($this->inTenant($this->tenantA(), fn () => Sport::find($used->id)->is_active));
    }

    public function test_requires_token_and_permission(): void
    {
        $this->getJson('/api/sports')->assertUnauthorized();

        // El utilero no tiene sports.*.
        $token = $this->token('inventory_manager');
        $this->withClubToken($token)->getJson('/api/sports')->assertForbidden()->assertJsonPath('code', 'forbidden');
        $this->withClubToken($token)->postJson('/api/sports', ['name' => 'Rugby'])->assertForbidden();
    }

    public function test_missing_returns_404(): void
    {
        $this->withClubToken($this->token())->getJson('/api/sports/999999')->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_a_sport_of_another_club_is_not_visible(): void
    {
        $sportB = $this->inTenant($this->tenantB(), fn () => Sport::factory()->create(['name' => 'Polo']));
        $token = $this->token();

        $this->withClubToken($token)->getJson("/api/sports/{$sportB->id}")->assertNotFound();
        $this->withClubToken($token)->patchJson("/api/sports/{$sportB->id}", ['name' => 'X'])->assertNotFound();
        $this->withClubToken($token)->deleteJson("/api/sports/{$sportB->id}")->assertNotFound();
        $this->withClubToken($token)->getJson('/api/sports')->assertJsonCount(0, 'data');

        $this->assertSame('Polo', $this->inTenant($this->tenantB(), fn () => Sport::find($sportB->id)->name));
    }
}
