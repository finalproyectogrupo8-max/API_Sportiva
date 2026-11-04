<?php

namespace Tests\Feature\Club;

use App\Models\Club\Player;
use App\Models\Club\Position;
use App\Models\Club\Sport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class PositionApiTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    private function token(string $role = 'admin'): string
    {
        return $this->clubToken($this->tenantA(), roles: [$role]);
    }

    public function test_lists_paginated_and_filters_by_sport_and_active_status(): void
    {
        [$football, $basketball] = $this->inTenant($this->tenantA(), function () {
            $football = Sport::factory()->create(['name' => 'Fútbol']);
            $basketball = Sport::factory()->create(['name' => 'Básquet']);
            for ($i = 1; $i <= 22; $i++) {
                Position::factory()->create(['sport_id' => $football->id, 'name' => "Posición {$i}"]);
            }
            Position::factory()->create(['sport_id' => $football->id, 'name' => 'Inactiva', 'is_active' => false]);
            Position::factory()->create(['sport_id' => $basketball->id, 'name' => 'Base']);

            return [$football, $basketball];
        });
        $token = $this->token();

        $this->withClubToken($token)->getJson('/api/positions?sport_id='.$football->id)
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.total', 22);

        $this->withClubToken($token)->getJson('/api/positions?sport_id='.$football->id.'&is_active=0')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Inactiva');

        $this->withClubToken($token)->getJson('/api/positions?sport_id='.$basketball->id)
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.sport.name', 'Básquet');

        $this->withClubToken($token)->getJson('/api/positions?sport_id=999999')->assertUnprocessable();
    }

    public function test_shows_one_position_with_its_sport(): void
    {
        $position = $this->inTenant($this->tenantA(), function () {
            $sport = Sport::factory()->create(['name' => 'Fútbol']);

            return Position::factory()->create(['sport_id' => $sport->id, 'name' => 'Arquero']);
        });

        $this->withClubToken($this->token())->getJson("/api/positions/{$position->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Arquero')
            ->assertJsonPath('data.sport.name', 'Fútbol');
    }

    public function test_creates_positions_and_validates_sport_and_unique_name(): void
    {
        $sport = $this->inTenant($this->tenantA(), fn () => Sport::factory()->create(['name' => 'Fútbol']));
        $token = $this->token();

        $this->withClubToken($token)->postJson('/api/positions', ['sport_id' => $sport->id, 'name' => 'Arquero'])
            ->assertCreated()->assertJsonPath('data.sport_id', $sport->id);
        $this->assertTrue($this->inTenant(
            $this->tenantA(),
            fn () => Position::query()->where('sport_id', $sport->id)->where('name', 'Arquero')->exists(),
        ));

        $this->withClubToken($token)->postJson('/api/positions', ['sport_id' => $sport->id, 'name' => 'Arquero'])
            ->assertUnprocessable()->assertJsonValidationErrors('name');

        $this->withClubToken($token)->postJson('/api/positions', ['sport_id' => 999999, 'name' => 'Arquero'])
            ->assertUnprocessable()->assertJsonValidationErrors('sport_id');
    }

    public function test_position_name_can_repeat_in_another_sport(): void
    {
        [$football, $basketball] = $this->inTenant($this->tenantA(), fn () => [
            Sport::factory()->create(['name' => 'Fútbol']),
            Sport::factory()->create(['name' => 'Básquet']),
        ]);
        $token = $this->token();

        $this->withClubToken($token)->postJson('/api/positions', ['sport_id' => $football->id, 'name' => 'Base'])->assertCreated();
        $this->withClubToken($token)->postJson('/api/positions', ['sport_id' => $basketball->id, 'name' => 'Base'])->assertCreated();
    }

    public function test_updates_only_sent_fields_and_checks_uniqueness_in_target_sport(): void
    {
        [$position, $other] = $this->inTenant($this->tenantA(), function () {
            $sport = Sport::factory()->create(['name' => 'Fútbol']);
            $other = Position::factory()->create(['sport_id' => $sport->id, 'name' => 'Arquero']);

            return [Position::factory()->create(['sport_id' => $sport->id, 'name' => 'Defensor']), $other];
        });
        $token = $this->token();

        $this->withClubToken($token)->patchJson("/api/positions/{$position->id}", ['name' => 'Central'])
            ->assertOk()->assertJsonPath('data.name', 'Central')->assertJsonPath('data.is_active', true);

        $this->withClubToken($token)->patchJson("/api/positions/{$position->id}", ['name' => $other->name])
            ->assertUnprocessable()->assertJsonValidationErrors('name');
    }

    public function test_delete_deactivates_position_without_losing_player_association(): void
    {
        [$position, $player] = $this->inTenant($this->tenantA(), function () {
            $sport = Sport::factory()->create(['name' => 'Fútbol']);
            $position = Position::factory()->create(['sport_id' => $sport->id, 'name' => 'Arquero']);
            $player = Player::factory()->create();
            $player->positions()->attach($position);

            return [$position, $player];
        });
        $token = $this->token();

        $this->withClubToken($token)->deleteJson("/api/positions/{$position->id}")->assertNoContent();
        $this->withClubToken($token)->getJson('/api/positions?sport_id='.$position->sport_id)
            ->assertOk()->assertJsonCount(0, 'data');
        $this->withClubToken($token)->getJson("/api/players/{$player->id}")
            ->assertOk()
            ->assertJsonPath('data.positions.0.id', $position->id)
            ->assertJsonPath('data.positions.0.is_active', false);

        $this->assertTrue($this->inTenant($this->tenantA(), fn () => Position::find($position->id)->is_active === false));
        $this->assertSame([$position->id], $this->inTenant($this->tenantA(), fn () => $player->positions()->pluck('positions.id')->all()));
    }

    public function test_requires_token_and_permission(): void
    {
        $this->getJson('/api/positions')->assertUnauthorized();

        $sport = $this->inTenant($this->tenantA(), fn () => Sport::factory()->create());
        $token = $this->token('inventory_manager');
        $this->withClubToken($token)->getJson('/api/positions')->assertForbidden();
        $this->withClubToken($token)->postJson('/api/positions', ['sport_id' => $sport->id, 'name' => 'Arquero'])->assertForbidden();
    }

    public function test_position_from_another_club_is_not_visible(): void
    {
        $position = $this->inTenant($this->tenantB(), function () {
            $sport = Sport::factory()->create(['name' => 'Fútbol']);

            return Position::factory()->create(['sport_id' => $sport->id, 'name' => 'Arquero']);
        });

        $this->withClubToken($this->token())->getJson("/api/positions/{$position->id}")->assertNotFound();
        $this->withClubToken($this->token())->patchJson("/api/positions/{$position->id}", ['name' => 'Defensor'])->assertNotFound();
        $this->withClubToken($this->token())->deleteJson("/api/positions/{$position->id}")->assertNotFound();
    }

    public function test_missing_position_returns_404(): void
    {
        $this->withClubToken($this->token())->getJson('/api/positions/999999')
            ->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_tenant_migrations_can_be_rolled_back_and_reapplied(): void
    {
        $positionsMigration = require base_path('database/migrations/tenant/2026_11_06_000001_create_positions_table.php');
        $playerPositionsMigration = require base_path('database/migrations/tenant/2026_11_06_000002_create_player_positions_table.php');

        $this->inTenant($this->tenantA(), function () use ($positionsMigration, $playerPositionsMigration) {
            $defaultConnection = DB::getDefaultConnection();
            DB::setDefaultConnection('tenant');

            try {
                $playerPositionsMigration->down();
                $positionsMigration->down();
                $this->assertFalse(Schema::connection('tenant')->hasTable('player_positions'));
                $this->assertFalse(Schema::connection('tenant')->hasTable('positions'));

                $positionsMigration->up();
                $playerPositionsMigration->up();
                $this->assertTrue(Schema::connection('tenant')->hasTable('positions'));
                $this->assertTrue(Schema::connection('tenant')->hasTable('player_positions'));
            } finally {
                DB::setDefaultConnection($defaultConnection);
            }
        });
    }
}
