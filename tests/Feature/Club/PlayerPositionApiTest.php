<?php

namespace Tests\Feature\Club;

use App\Models\Club\Player;
use App\Models\Club\Position;
use App\Models\Club\Sport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class PlayerPositionApiTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    private function token(): string
    {
        return $this->clubToken($this->tenantA(), roles: ['admin']);
    }

    public function test_creates_player_with_multiple_positions_across_sports(): void
    {
        $positions = $this->inTenant($this->tenantA(), function () {
            $football = Sport::factory()->create(['name' => 'Fútbol']);
            $basketball = Sport::factory()->create(['name' => 'Básquet']);

            return [
                Position::factory()->create(['sport_id' => $football->id, 'name' => 'Arquero']),
                Position::factory()->create(['sport_id' => $football->id, 'name' => 'Defensor']),
                Position::factory()->create(['sport_id' => $basketball->id, 'name' => 'Base']),
            ];
        });
        $token = $this->token();

        $response = $this->withClubToken($token)->postJson('/api/players', [
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
            'birth_date' => '2010-01-01',
            'preferred_position' => 'Arquera histórica',
            'position_ids' => array_map(fn (Position $position) => $position->id, $positions),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.preferred_position', 'Arquera histórica')
            ->assertJsonCount(3, 'data.positions')
            ->assertJsonPath('data.positions.0.sport.name', 'Fútbol')
            ->assertJsonPath('data.positions.2.sport.name', 'Básquet');
    }

    public function test_update_replaces_positions_only_when_the_field_is_sent(): void
    {
        [$player, $first, $second] = $this->inTenant($this->tenantA(), function () {
            $sport = Sport::factory()->create(['name' => 'Fútbol']);
            $first = Position::factory()->create(['sport_id' => $sport->id, 'name' => 'Arquero']);
            $second = Position::factory()->create(['sport_id' => $sport->id, 'name' => 'Defensor']);
            $player = Player::factory()->create(['preferred_position' => 'Arquero histórico']);
            $player->positions()->attach([$first->id, $second->id]);

            return [$player, $first, $second];
        });
        $token = $this->token();

        $this->withClubToken($token)->patchJson("/api/players/{$player->id}", ['first_name' => 'Nombre actualizado'])
            ->assertOk()->assertJsonCount(2, 'data.positions')
            ->assertJsonPath('data.preferred_position', 'Arquero histórico');

        $this->withClubToken($token)->patchJson("/api/players/{$player->id}", ['position_ids' => [$second->id]])
            ->assertOk()->assertJsonCount(1, 'data.positions')
            ->assertJsonPath('data.positions.0.id', $second->id);

        $this->withClubToken($token)->patchJson("/api/players/{$player->id}", ['position_ids' => []])
            ->assertOk()->assertJsonCount(0, 'data.positions');
    }

    public function test_rejects_nonexistent_foreign_duplicate_or_inactive_positions_without_changing_assignments(): void
    {
        [$player, $valid] = $this->inTenant($this->tenantA(), function () {
            $sport = Sport::factory()->create(['name' => 'Fútbol']);
            $valid = Position::factory()->create(['sport_id' => $sport->id, 'name' => 'Arquero']);
            $player = Player::factory()->create();
            $player->positions()->attach($valid);

            return [$player, $valid];
        });
        $foreign = $this->inTenant($this->tenantB(), function () {
            $sport = Sport::factory()->create(['name' => 'Fútbol']);

            Position::factory()->create(['sport_id' => $sport->id, 'name' => 'Ajena 1']);
            Position::factory()->create(['sport_id' => $sport->id, 'name' => 'Ajena 2']);

            return Position::factory()->create(['sport_id' => $sport->id, 'name' => 'Ajena 3']);
        });
        $inactive = $this->inTenant($this->tenantA(), function () use ($valid) {
            return Position::factory()->create(['sport_id' => $valid->sport_id, 'name' => 'Inactiva', 'is_active' => false]);
        });
        $token = $this->token();

        foreach ([
            [[999999], 'position_ids.0'],
            [[$valid->id, 999999], 'position_ids.1'],
            [[$foreign->id], 'position_ids.0'],
            [[$inactive->id], 'position_ids.0'],
        ] as [$positionIds, $errorKey]) {
            $this->withClubToken($token)->patchJson("/api/players/{$player->id}", ['position_ids' => $positionIds])
                ->assertUnprocessable()->assertJsonValidationErrors($errorKey);
        }

        $this->withClubToken($token)->patchJson("/api/players/{$player->id}", ['position_ids' => [$valid->id, $valid->id]])
            ->assertUnprocessable()->assertJsonValidationErrors('position_ids.1');

        $this->assertSame([$valid->id], $this->inTenant(
            $this->tenantA(),
            fn () => $player->positions()->pluck('positions.id')->all(),
        ));
    }
}
