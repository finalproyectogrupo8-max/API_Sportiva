<?php

namespace Tests\Feature\Club;

use App\Models\Club\Category;
use App\Models\Club\GameMatch;
use App\Models\Club\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** Sedes del club y sede del partido de local (TIZ-39). */
class VenueApiTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    private function token(string $role = 'admin'): string
    {
        return $this->clubToken($this->tenantA(), roles: [$role]);
    }

    public function test_creates_lists_updates_and_builds_the_maps_link(): void
    {
        $token = $this->token();

        $id = $this->withClubToken($token)->postJson('/api/venues', [
            'name' => 'Parque deportivo',
            'address' => 'Ruta 4 km 12, Lomas de Zamora',
            'latitude' => -34.7612345,
            'longitude' => -58.4012345,
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Parque deportivo')
            ->assertJsonPath('data.latitude', -34.7612345)
            ->assertJsonPath('data.maps_url', 'https://www.google.com/maps/search/?api=1&query=-34.7612345%2C-58.4012345')
            ->json('data.id');

        $this->withClubToken($token)->postJson('/api/venues', ['name' => 'Sede principal', 'address' => 'Av. Siempreviva 742'])
            ->assertCreated()
            ->assertJsonPath('data.latitude', null)
            ->assertJsonPath('data.maps_url', 'https://www.google.com/maps/search/?api=1&query=Av.%20Siempreviva%20742');

        $this->withClubToken($token)->getJson('/api/venues')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.name', 'Parque deportivo');
        $this->withClubToken($token)->patchJson("/api/venues/{$id}", ['is_active' => false])->assertOk()->assertJsonPath('data.is_active', false);
        $this->withClubToken($token)->getJson('/api/venues?is_active=1')->assertJsonCount(1, 'data');
    }

    public function test_validates(): void
    {
        $this->withClubToken($this->token())->postJson('/api/venues', ['latitude' => 120])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'address', 'latitude', 'longitude']);
    }

    public function test_permissions(): void
    {
        $venue = $this->inTenant($this->tenantA(), fn () => Venue::factory()->create());

        $this->getJson('/api/venues')->assertUnauthorized();
        // El profe la ve (la elige al cargar un partido) pero no la edita.
        $coach = $this->token('coach');
        $this->withClubToken($coach)->getJson('/api/venues')->assertOk();
        $this->withClubToken($coach)->postJson('/api/venues', ['name' => 'X', 'address' => 'Y'])->assertForbidden();
        $this->withClubToken($coach)->deleteJson("/api/venues/{$venue->id}")->assertForbidden();
        $this->withClubToken($this->token('coordinator'))->patchJson("/api/venues/{$venue->id}", ['name' => 'Nueva'])->assertOk();
    }

    public function test_a_venue_of_another_club_is_not_visible(): void
    {
        $venue = $this->inTenant($this->tenantB(), fn () => Venue::factory()->create());

        $this->withClubToken($this->token())->getJson("/api/venues/{$venue->id}")->assertNotFound();
    }

    public function test_home_match_takes_place_and_link_from_the_venue(): void
    {
        [$category, $venue] = $this->inTenant($this->tenantA(), fn () => [
            Category::factory()->create(),
            Venue::factory()->create(['name' => 'Parque deportivo', 'address' => 'Ruta 4 km 12', 'latitude' => -34.5, 'longitude' => -58.5]),
        ]);
        $token = $this->token();

        $id = $this->withClubToken($token)->postJson('/api/matches', [
            'category_id' => $category->id,
            'opponent' => 'Club Norte',
            'match_date' => '2026-11-07 18:00:00',
            'is_home' => true,
            'venue_id' => $venue->id,
            'venue' => 'se ignora',
        ])->assertCreated()
            ->assertJsonPath('data.venue_id', $venue->id)
            ->assertJsonPath('data.venue', 'Parque deportivo · Ruta 4 km 12')
            ->assertJsonPath('data.maps_url', 'https://www.google.com/maps/search/?api=1&query=-34.5%2C-58.5')
            ->json('data.id');

        // De visitante: sin sede, el link sale de la dirección escrita.
        $this->withClubToken($token)->patchJson("/api/matches/{$id}", ['is_home' => false, 'venue_id' => null, 'venue' => 'Cancha de Norte, Calle 9 123', 'maps_url' => null])
            ->assertOk()
            ->assertJsonPath('data.venue_id', null)
            ->assertJsonPath('data.maps_url', 'https://www.google.com/maps/search/?api=1&query=Cancha%20de%20Norte%2C%20Calle%209%20123');
    }

    public function test_cannot_delete_a_venue_with_upcoming_matches(): void
    {
        [$used, $free] = $this->inTenant($this->tenantA(), function () {
            $used = Venue::factory()->create();
            GameMatch::factory()->create(['venue_id' => $used->id, 'match_date' => now()->addWeek(), 'status' => 'scheduled']);

            return [$used, Venue::factory()->create()];
        });
        $token = $this->token();

        $this->withClubToken($token)->deleteJson("/api/venues/{$used->id}")->assertStatus(409);
        $this->withClubToken($token)->deleteJson("/api/venues/{$free->id}")->assertNoContent();
        $this->withClubToken($token)->getJson("/api/venues/{$free->id}")->assertNotFound();
    }
}
