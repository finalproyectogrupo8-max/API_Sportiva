<?php

namespace Tests\Feature\Club;

use App\Models\Club\Category;
use App\Models\Club\Competition;
use App\Models\Club\CompetitionRequirement;
use App\Models\Club\GameMatch;
use App\Models\Club\MatchCallup;
use App\Models\Club\Player;
use App\Models\Club\PlayerCategoryMembership;
use App\Models\Club\PlayerContact;
use App\Models\Club\PlayerRequirement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** Convocatorias y páginas públicas (TIZ-33). */
class CallupApiTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    private function token(string $role = 'admin'): string
    {
        return $this->clubToken($this->tenantA(), roles: [$role]);
    }

    /** Sub-15 con la Liga (pide DNI). Ana y Bea están habilitadas; Caro no. */
    private function setUpMatch(): array
    {
        return $this->inTenant($this->tenantA(), function () {
            $category = Category::factory()->create(['name' => 'Sub-15']);
            $competition = Competition::factory()->create(['sport_id' => $category->sport_id, 'name' => 'Liga Metropolitana']);
            $competition->categories()->attach($category->id);
            $dni = CompetitionRequirement::create(['competition_id' => $competition->id, 'name' => 'DNI']);

            $players = [];
            foreach ([['Ana', 'Acosta', 4, true], ['Bea', 'Benítez', 7, true], ['Caro', 'Castro', 9, false]] as [$first, $last, $number, $ok]) {
                $player = Player::factory()->create(['first_name' => $first, 'last_name' => $last, 'document_number' => '40111222']);
                PlayerCategoryMembership::create(['player_id' => $player->id, 'category_id' => $category->id, 'start_date' => '2026-03-01', 'shirt_number' => $number, 'status' => 'active']);
                if ($ok) {
                    PlayerRequirement::create(['player_id' => $player->id, 'competition_requirement_id' => $dni->id, 'status' => 'approved']);
                }
                $players[$first] = $player;
            }
            PlayerContact::create(['player_id' => $players['Ana']->id, 'name' => 'Mamá de Ana', 'relationship' => 'mother', 'phone' => '+54 9 11 5555-0000', 'is_primary' => true]);

            $match = GameMatch::factory()->create([
                'category_id' => $category->id, 'competition_id' => $competition->id, 'opponent' => 'Club Norte',
                // En UTC, como lo manda el front: en Buenos Aires son las 10:00 y las 09:15.
                'match_date' => '2026-11-07 13:00:00', 'meet_at' => '2026-11-07 12:15:00', 'venue' => 'Estadio Cubierto',
                'is_home' => true, 'uniform' => 'home', 'uniform_color' => 'Azul',
            ]);

            return ['match' => $match, ...$players];
        });
    }

    public function test_builds_the_callup_only_with_eligible_players(): void
    {
        $d = $this->setUpMatch();
        $token = $this->token();
        $url = "/api/matches/{$d['match']->id}/callup";

        $this->withClubToken($token)->getJson($url)
            ->assertOk()
            ->assertJsonPath('data.published_at', null)
            ->assertJsonCount(3, 'data.squad')
            ->assertJsonPath('data.squad.2.player_name', 'Caro Castro')
            ->assertJsonPath('data.squad.2.is_eligible', false)
            ->assertJsonPath('data.squad.2.missing', ['DNI']);

        $this->withClubToken($token)->putJson($url, ['players' => [['player_id' => $d['Ana']->id], ['player_id' => $d['Caro']->id]]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['players.1.player_id' => 'Caro Castro no está habilitado: falta DNI.']);
        $this->withClubToken($token)->putJson($url, ['players' => [['player_id' => $d['Ana']->id], ['player_id' => $d['Bea']->id, 'shirt_number' => 4]]])
            ->assertUnprocessable()->assertJsonValidationErrors(['players.1.shirt_number' => 'El número 4 está repetido.']);

        $this->withClubToken($token)->putJson($url, ['players' => [['player_id' => $d['Ana']->id], ['player_id' => $d['Bea']->id]]])
            ->assertOk()
            ->assertJsonCount(2, 'data.callups')
            ->assertJsonPath('data.callups.0.shirt_number', 4)
            ->assertJsonPath('data.callups.0.response', 'pending')
            ->assertJsonPath('data.pending', 2)
            // Sin publicar todavía no hay links para compartir.
            ->assertJsonPath('data.callups.0.whatsapp_url', null);
    }

    public function test_publishes_and_family_confirms_from_the_personal_link(): void
    {
        $d = $this->setUpMatch();
        $token = $this->token();
        $url = "/api/matches/{$d['match']->id}/callup";

        $this->withClubToken($token)->postJson("{$url}/publish")
            ->assertUnprocessable()->assertJsonValidationErrors(['players' => 'Convocá al menos un jugador antes de publicar.']);
        $this->withClubToken($token)->putJson($url, ['players' => [['player_id' => $d['Ana']->id], ['player_id' => $d['Bea']->id]]]);

        $sheet = $this->withClubToken($token)->postJson("{$url}/publish")->assertOk()->json('data');
        $this->assertNotNull($sheet['published_at']);
        $this->assertStringStartsWith(config('app.frontend_url').'/convocatoria/'.$this->tenantA()->slug.'.', $sheet['public_url']);
        $this->assertStringContainsString('vs Club Norte', $sheet['group_message']);
        $this->assertStringContainsString('Presentación 09:15 · Partido 10:00', $sheet['group_message']);
        $this->assertStringContainsString('Sábado 7 de noviembre', $sheet['group_message']);
        $this->assertStringContainsString('Camiseta titular (Azul)', $sheet['group_message']);
        $this->assertStringContainsString("*Convocados (2)*\n4 · Ana Acosta\n7 · Bea Benítez", $sheet['group_message']);
        $this->assertStringStartsWith('https://wa.me/5491155550000?text=', $sheet['callups'][0]['whatsapp_url']);
        $this->assertNull($sheet['callups'][1]['whatsapp_url']);

        // La página pública: datos del partido y nombres sin datos personales.
        $publicToken = substr($sheet['public_url'], strlen(config('app.frontend_url').'/convocatoria/'));
        $page = $this->getJson("/api/public/callups/{$publicToken}")
            ->assertOk()
            ->assertJsonPath('data.match.opponent', 'Club Norte')
            ->assertJsonPath('data.match.competition', 'Liga Metropolitana')
            ->assertJsonPath('data.players.0.name', 'Ana A.')
            ->assertJsonPath('data.players.0.shirt_number', 4)
            ->getContent();
        $this->assertStringNotContainsString('40111222', $page);
        $this->assertStringNotContainsString('5555', $page);
        $ics = $this->get("/api/public/callups/{$publicToken}/calendar.ics")->assertOk()->assertHeader('Content-Type', 'text/calendar; charset=utf-8')->getContent();
        $this->assertStringContainsString('DTSTART:20261107T121500Z', $ics);
        $this->assertStringContainsString('Presentación 09:15', $ics);

        // Ana confirma con su link; Bea dice que no puede.
        $anaToken = substr($sheet['callups'][0]['personal_url'], strlen(config('app.frontend_url').'/convocatoria/responder/'));
        $beaToken = substr($sheet['callups'][1]['personal_url'], strlen(config('app.frontend_url').'/convocatoria/responder/'));
        $this->getJson("/api/public/callups/respond/{$anaToken}")->assertOk()->assertJsonPath('data.me.name', 'Ana A.')->assertJsonPath('data.page.match.opponent', 'Club Norte')->assertJsonPath('data.me.response', 'pending');
        $this->postJson("/api/public/callups/respond/{$anaToken}", ['response' => 'going'])->assertOk()->assertJsonPath('data.me.response', 'going');
        $this->postJson("/api/public/callups/respond/{$beaToken}", ['response' => 'not_going', 'note' => 'Viaje familiar'])->assertOk();
        $this->postJson("/api/public/callups/respond/{$beaToken}", ['response' => 'tal vez'])->assertUnprocessable();

        $this->withClubToken($token)->getJson($url)
            ->assertJsonPath('data.going', 1)
            ->assertJsonPath('data.not_going', 1)
            ->assertJsonPath('data.callups.1.response_note', 'Viaje familiar');

        $this->getJson('/api/public/callups/'.$this->tenantA()->slug.'.no-existe')->assertNotFound();
        $this->getJson('/api/public/callups/respond/sin-club')->assertNotFound();
    }

    public function test_changes_after_publishing_ask_to_notify_again(): void
    {
        $d = $this->setUpMatch();
        $token = $this->token();
        $url = "/api/matches/{$d['match']->id}/callup";
        $this->withClubToken($token)->putJson($url, ['players' => [['player_id' => $d['Ana']->id]]]);
        $this->withClubToken($token)->postJson("{$url}/publish");

        $this->withClubToken($token)->patchJson("/api/matches/{$d['match']->id}", ['notes' => 'Llevar pelotas'])->assertOk();
        $this->withClubToken($token)->getJson($url)->assertJsonPath('data.updated_after_publish', false);

        $this->withClubToken($token)->patchJson("/api/matches/{$d['match']->id}", ['venue' => 'Cancha 2'])->assertOk();
        $this->withClubToken($token)->getJson($url)->assertJsonPath('data.updated_after_publish', true);

        // Republicar: mismo link, sin la marca.
        $first = $this->withClubToken($token)->getJson($url)->json('data.public_url');
        $this->withClubToken($token)->postJson("{$url}/publish")
            ->assertJsonPath('data.updated_after_publish', false)
            ->assertJsonPath('data.public_url', $first);
    }

    public function test_permissions_and_isolation(): void
    {
        $d = $this->setUpMatch();
        $url = "/api/matches/{$d['match']->id}/callup";

        $this->getJson($url)->assertUnauthorized();
        // El utilero ve la convocatoria (prepara camisetas) pero no la arma.
        $this->withClubToken($this->token('inventory_manager'))->getJson($url)->assertOk();
        $this->withClubToken($this->token('inventory_manager'))->putJson($url, ['players' => []])->assertForbidden();
        // Un profe sin esa categoría no la ve.
        $this->withClubToken($this->token('coach'))->getJson($url)->assertNotFound();
        // Mantenimiento no tiene convocatorias.
        $this->withClubToken($this->token('maintenance_manager'))->getJson($url)->assertForbidden();

        $this->withClubToken($this->clubToken($this->tenantB(), roles: ['admin']))->getJson($url)->assertNotFound();
        $this->assertSame(0, $this->inTenant($this->tenantA(), fn () => MatchCallup::query()->count()));
    }
}
