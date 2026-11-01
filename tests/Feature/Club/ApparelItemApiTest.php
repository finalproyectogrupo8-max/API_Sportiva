<?php

namespace Tests\Feature\Club;

use App\Models\Club\ApparelItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** ABM de prendas del catálogo (apparel_items). */
class ApparelItemApiTest extends TestCase
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
            ApparelItem::factory()->count(22)->sequence(fn ($s) => [
                'name' => 'Prenda '.str_pad((string) $s->index, 2, '0', STR_PAD_LEFT),
            ])->create();
            ApparelItem::factory()->create(['name' => 'Campera Antigua', 'is_active' => false]);
        });
        $token = $this->token();

        $this->withClubToken($token)->getJson('/api/apparel-items')
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.total', 23)
            ->assertJsonStructure(['data', 'links', 'meta']);

        $this->withClubToken($token)->getJson('/api/apparel-items?is_active=0')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Campera Antigua');

        $this->withClubToken($token)->getJson('/api/apparel-items?search=Prenda 0&sort=-name')
            ->assertOk()->assertJsonCount(10, 'data')->assertJsonPath('data.0.name', 'Prenda 09');
    }

    public function test_shows_one(): void
    {
        $item = $this->inTenant($this->tenantA(), fn () => ApparelItem::factory()->create([
            'name' => 'Camiseta Oficial 2026',
            'description' => 'Manga corta con escudo bordado',
            'is_active' => true,
        ]));

        $this->withClubToken($this->token())->getJson("/api/apparel-items/{$item->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Camiseta Oficial 2026')
            ->assertJsonPath('data.description', 'Manga corta con escudo bordado')
            ->assertJsonPath('data.is_active', true);
    }

    public function test_creates_and_validates(): void
    {
        $token = $this->token('inventory_manager');

        $this->withClubToken($token)->postJson('/api/apparel-items', [
            'name' => 'Short Alternativo Negro',
            'description' => 'Talles juveniles y adultos',
            'is_active' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Short Alternativo Negro')
            ->assertJsonPath('data.is_active', true);

        $this->assertTrue($this->inTenant($this->tenantA(), fn () => ApparelItem::where('name', 'Short Alternativo Negro')->exists()));

        // Validación de campo requerido
        $this->withClubToken($token)->postJson('/api/apparel-items', [
            'name' => '',
        ])->assertUnprocessable()->assertJsonValidationErrors('name');
    }

    public function test_updates_only_what_was_sent(): void
    {
        $item = $this->inTenant($this->tenantA(), fn () => ApparelItem::factory()->create([
            'name' => 'Medias de Juego',
            'description' => 'Color azul marino',
            'is_active' => true,
        ]));

        $this->withClubToken($this->token('inventory_manager'))
            ->putJson("/api/apparel-items/{$item->id}", [
                'name' => 'Medias Oficiales de Juego',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Medias Oficiales de Juego')
            ->assertJsonPath('data.description', 'Color azul marino');
    }

    public function test_delete_is_soft(): void
    {
        $item = $this->inTenant($this->tenantA(), fn () => ApparelItem::factory()->create(['name' => 'Chomba']));

        $this->withClubToken($this->token('inventory_manager'))
            ->deleteJson("/api/apparel-items/{$item->id}")
            ->assertNoContent();

        $this->withClubToken($this->token())->getJson("/api/apparel-items/{$item->id}")->assertNotFound();
        $this->assertNotNull($this->inTenant($this->tenantA(), fn () => ApparelItem::withTrashed()->find($item->id)?->deleted_at));
    }

    public function test_requires_token_and_permission(): void
    {
        $this->getJson('/api/apparel-items')->assertUnauthorized();

        // Sin permisos de creación
        $coachToken = $this->token('coach');
        $this->withClubToken($coachToken)->postJson('/api/apparel-items', ['name' => 'Item'])
            ->assertForbidden();
    }

    public function test_missing_returns_404(): void
    {
        $this->withClubToken($this->token())->getJson('/api/apparel-items/999999')->assertNotFound();
    }

    public function test_apparel_item_of_another_club_is_not_visible(): void
    {
        $itemB = $this->inTenant($this->tenantB(), fn () => ApparelItem::factory()->create(['name' => 'Prenda Club B']));

        $this->withClubToken($this->clubToken($this->tenantA()))
            ->getJson("/api/apparel-items/{$itemB->id}")
            ->assertNotFound();
    }
}
