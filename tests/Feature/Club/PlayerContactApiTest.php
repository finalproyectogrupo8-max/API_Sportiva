<?php

namespace Tests\Feature\Club;

use App\Models\Club\Player;
use App\Models\Club\PlayerContact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** Tutores y contactos del jugador (TIZ-15). */
class PlayerContactApiTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    private function token(string $role = 'admin'): string
    {
        return $this->clubToken($this->tenantA(), roles: [$role]);
    }

    public function test_manages_contacts_with_a_single_primary(): void
    {
        $player = $this->inTenant($this->tenantA(), fn () => Player::factory()->create());
        $token = $this->token();
        $url = "/api/players/{$player->id}/contacts";

        $mother = $this->withClubToken($token)->postJson($url, ['name' => 'Ana Acosta', 'relationship' => 'mother', 'phone' => '1155550000'])
            ->assertCreated()->assertJsonPath('data.is_primary', true)->json('data.id');
        $father = $this->withClubToken($token)->postJson($url, ['name' => 'Juan Acosta', 'relationship' => 'father', 'email' => 'juan@example.com'])
            ->assertCreated()->assertJsonPath('data.is_primary', false)->json('data.id');

        $this->withClubToken($token)->patchJson("/api/player-contacts/{$father}", ['is_primary' => true])->assertOk()->assertJsonPath('data.is_primary', true);
        $this->withClubToken($token)->getJson($url)
            ->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $father)
            ->assertJsonPath('data.1.is_primary', false);

        // Al borrar el principal, el otro pasa a serlo.
        $this->withClubToken($token)->deleteJson("/api/player-contacts/{$father}")->assertNoContent();
        $this->assertTrue($this->inTenant($this->tenantA(), fn () => PlayerContact::find($mother)->is_primary));
    }

    public function test_validates_contacts(): void
    {
        $player = $this->inTenant($this->tenantA(), fn () => Player::factory()->create());

        $this->withClubToken($this->token())->postJson("/api/players/{$player->id}/contacts", ['name' => 'Ana', 'relationship' => 'tía'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['relationship', 'phone' => 'Cargá un teléfono o un mail.']);
    }

    public function test_requires_permission_and_isolates_clubs(): void
    {
        $player = $this->inTenant($this->tenantA(), fn () => Player::factory()->create());
        // Un id que existe en el club B y no en el A.
        $playerB = $this->inTenant($this->tenantB(), fn () => Player::factory()->count(2)->create()->last());

        $this->withClubToken($this->token('inventory_manager'))->getJson("/api/players/{$player->id}/contacts")->assertForbidden();
        // El profe ve contactos de sus jugadores, pero no los edita.
        $this->withClubToken($this->token('coach'))->postJson("/api/players/{$player->id}/contacts", ['name' => 'Ana', 'relationship' => 'mother', 'phone' => '1'])->assertForbidden();
        $this->withClubToken($this->token())->getJson("/api/players/{$playerB->id}/contacts")->assertNotFound();
    }
}
