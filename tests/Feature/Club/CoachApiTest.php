<?php

namespace Tests\Feature\Club;

use App\Models\Club\Category;
use App\Models\Club\Coach;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/**
 * ABM de profes (club-catalog-crud): los 10 casos de docs/guia-abm.md §11.
 * El alcance "sus categorías" del rol profe está en ClubPermissionsTest.
 */
class CoachApiTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    private function token(string $role = 'admin'): string
    {
        return $this->clubToken($this->tenantA(), roles: [$role]);
    }

    private function payload(array $overrides = []): array
    {
        return ['first_name' => 'Laura', 'last_name' => 'Gómez', 'phone' => '1155550000', 'email' => 'laura@example.com', 'status' => 'active', ...$overrides];
    }

    public function test_lists_paginated_with_filters(): void
    {
        $this->inTenant($this->tenantA(), function () {
            Coach::factory()->count(21)->sequence(fn ($s) => ['last_name' => 'Apellido '.str_pad((string) $s->index, 2, '0', STR_PAD_LEFT)])->create();
            Coach::factory()->create(['first_name' => 'Mario', 'last_name' => 'Zárate', 'status' => 'inactive']);
        });
        $token = $this->token();

        $this->withClubToken($token)->getJson('/api/coaches')
            ->assertOk()->assertJsonCount(20, 'data')->assertJsonPath('meta.total', 22)
            ->assertJsonPath('data.0.last_name', 'Apellido 00');
        $this->withClubToken($token)->getJson('/api/coaches?status=inactive')
            ->assertOk()->assertJsonCount(1, 'data');
        $this->withClubToken($token)->getJson('/api/coaches?search=Mario')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.last_name', 'Zárate');
    }

    public function test_shows_one(): void
    {
        $coach = $this->inTenant($this->tenantA(), fn () => Coach::factory()->create($this->payload()));

        $this->withClubToken($this->token())->getJson("/api/coaches/{$coach->id}")
            ->assertOk()->assertJsonPath('data.email', 'laura@example.com')->assertJsonPath('data.club_user_id', null);
    }

    public function test_creates_and_validates(): void
    {
        $token = $this->token();

        $this->withClubToken($token)->postJson('/api/coaches', $this->payload())
            ->assertCreated()->assertJsonPath('data.last_name', 'Gómez');
        $this->assertTrue($this->inTenant($this->tenantA(), fn () => Coach::where('last_name', 'Gómez')->exists()));

        $this->withClubToken($token)->postJson('/api/coaches', $this->payload(['email' => 'no-es-mail', 'status' => 'retirado', 'club_user_id' => 999999]))
            ->assertUnprocessable()->assertJsonValidationErrors(['email', 'status', 'club_user_id']);
    }

    public function test_updates_only_what_was_sent(): void
    {
        $coach = $this->inTenant($this->tenantA(), fn () => Coach::factory()->create($this->payload()));

        $this->withClubToken($this->token())->patchJson("/api/coaches/{$coach->id}", ['phone' => null])
            ->assertOk()->assertJsonPath('data.phone', null)->assertJsonPath('data.email', 'laura@example.com');
    }

    public function test_delete_is_soft(): void
    {
        $coach = $this->inTenant($this->tenantA(), fn () => Coach::factory()->create());
        $token = $this->token();

        $this->withClubToken($token)->deleteJson("/api/coaches/{$coach->id}")->assertNoContent();
        $this->withClubToken($token)->getJson("/api/coaches/{$coach->id}")->assertNotFound();
        $this->assertNotNull($this->inTenant($this->tenantA(), fn () => Coach::withTrashed()->find($coach->id)?->deleted_at));
    }

    public function test_requires_token_and_permission(): void
    {
        $this->getJson('/api/coaches')->assertUnauthorized();

        $token = $this->token('inventory_manager');
        $this->withClubToken($token)->getJson('/api/coaches')->assertForbidden()->assertJsonPath('code', 'forbidden');
        $this->withClubToken($this->token('coach'))->postJson('/api/coaches', $this->payload())->assertForbidden();
    }

    public function test_missing_returns_404(): void
    {
        $this->withClubToken($this->token())->getJson('/api/coaches/999999')->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_a_coach_of_another_club_is_not_visible(): void
    {
        $coachB = $this->inTenant($this->tenantB(), fn () => Coach::factory()->create(['last_name' => 'Del club B']));
        $token = $this->token();

        $this->withClubToken($token)->getJson("/api/coaches/{$coachB->id}")->assertNotFound();
        $this->withClubToken($token)->patchJson("/api/coaches/{$coachB->id}", ['last_name' => 'X'])->assertNotFound();
        $this->withClubToken($token)->deleteJson("/api/coaches/{$coachB->id}")->assertNotFound();

        $this->assertSame('Del club B', $this->inTenant($this->tenantB(), fn () => Coach::find($coachB->id)->last_name));
    }

    public function test_lists_current_categories_of_each_coach(): void
    {
        $coach = $this->inTenant($this->tenantA(), function () {
            $coach = Coach::factory()->create();
            $u15 = Category::factory()->create(['name' => 'U15']);
            $u17 = Category::factory()->create(['name' => 'U17']);
            DB::connection('tenant')->table('category_coaches')->insert([
                ['category_id' => $u15->id, 'coach_id' => $coach->id, 'assignment_type' => 'head_coach', 'start_date' => '2026-01-01', 'end_date' => null],
                ['category_id' => $u17->id, 'coach_id' => $coach->id, 'assignment_type' => 'assistant', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31'],
            ]);

            return $coach;
        });
        $token = $this->token();

        $this->withClubToken($token)->getJson('/api/coaches')
            ->assertOk()->assertJsonCount(1, 'data.0.assignments')
            ->assertJsonPath('data.0.assignments.0.category_name', 'U15')
            ->assertJsonPath('data.0.assignments.0.assignment_type', 'head_coach');
        $this->withClubToken($token)->getJson("/api/coaches/{$coach->id}")
            ->assertOk()->assertJsonPath('data.assignments.0.category_name', 'U15');
    }
}
