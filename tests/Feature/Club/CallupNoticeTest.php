<?php

namespace Tests\Feature\Club;

use App\Club\Callups\CallupNoticeMode;
use App\Jobs\Club\NotifyCallup;
use App\Jobs\Club\SendCallupNotice;
use App\Mail\CallupNoticeMail;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** Aviso de la convocatoria por mail (TIZ-37). */
class CallupNoticeTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    private function token(string $role = 'admin'): string
    {
        return $this->clubToken($this->tenantA(), roles: [$role]);
    }

    /**
     * Sub-15 habilitada. Ana y Dani son hermanas: su madre es la misma (con el email en otra
     * capitalización). Bea tiene a su padre. Eva no tiene email (solo teléfono).
     */
    private function setUpMatch(): array
    {
        return $this->inTenant($this->tenantA(), function () {
            $category = Category::factory()->create(['name' => 'Sub-15']);
            $competition = Competition::factory()->create(['sport_id' => $category->sport_id, 'name' => 'Liga Metropolitana']);
            $competition->categories()->attach($category->id);
            $dni = CompetitionRequirement::create(['competition_id' => $competition->id, 'name' => 'DNI']);

            $contacts = [
                'Ana' => ['Mamá de Ana', 'mother', 'mama@example.test'],
                'Dani' => ['Mamá de Dani', 'mother', ' Mama@Example.test '],
                'Bea' => ['Papá de Bea', 'father', 'papa.bea@example.test'],
                'Eva' => ['Abuela de Eva', 'guardian', null],
            ];
            $players = [];
            foreach (['Ana' => 4, 'Dani' => 5, 'Bea' => 7, 'Eva' => 9] as $first => $number) {
                $player = Player::factory()->create(['first_name' => $first, 'last_name' => 'Pérez']);
                PlayerCategoryMembership::create(['player_id' => $player->id, 'category_id' => $category->id, 'start_date' => '2026-03-01', 'shirt_number' => $number, 'status' => 'active']);
                PlayerRequirement::create(['player_id' => $player->id, 'competition_requirement_id' => $dni->id, 'status' => 'approved']);
                [$name, $relationship, $email] = $contacts[$first];
                PlayerContact::create(['player_id' => $player->id, 'name' => $name, 'relationship' => $relationship, 'phone' => '+54 9 11 5555-0000', 'email' => $email, 'is_primary' => true]);
                $players[$first] = $player;
            }

            $match = GameMatch::factory()->create([
                'category_id' => $category->id, 'competition_id' => $competition->id, 'opponent' => 'Club Norte',
                'match_date' => '2026-11-07 13:00:00', 'meet_at' => '2026-11-07 12:15:00', 'venue' => 'Estadio Cubierto', 'is_home' => true,
            ]);

            return ['match' => $match, ...$players];
        });
    }

    private function callUp(array $d, array $names): void
    {
        $this->withClubToken($this->token())
            ->putJson("/api/matches/{$d['match']->id}/callup", ['players' => array_map(fn ($n) => ['player_id' => $d[$n]->id], $names)])
            ->assertOk();
    }

    private function sentTo(string $email): array
    {
        return Mail::sent(CallupNoticeMail::class, fn (CallupNoticeMail $m) => $m->hasTo($email))->values()->all();
    }

    public function test_publishing_mails_each_primary_contact_once_with_the_personal_links(): void
    {
        Mail::fake();
        $d = $this->setUpMatch();
        $url = "/api/matches/{$d['match']->id}/callup";
        $this->callUp($d, ['Ana', 'Dani', 'Bea', 'Eva']);

        $sheet = $this->withClubToken($this->token())->postJson("{$url}/publish")->assertOk()->json('data');

        // La madre de Ana y Dani recibe un solo mail, con los dos links; Eva no tiene email.
        Mail::assertSentCount(2);
        [$mother] = $this->sentTo('mama@example.test');
        $this->assertSame(CallupNoticeMode::Published, $mother->mode);
        $this->assertSame(['Ana', 'Dani'], array_column($mother->players, 'name'));
        $this->assertSame([$sheet['callups'][0]['personal_url'], $sheet['callups'][1]['personal_url']], array_column($mother->players, 'url'));
        $this->assertStringStartsWith('Convocatoria Sub-15 · vs Club Norte', $mother->subjectLine);
        $this->assertCount(1, $this->sentTo('papa.bea@example.test'));

        $rows = collect($this->withClubToken($this->token())->getJson($url)->json('data.callups'))->keyBy('player_name');
        $this->assertTrue($rows['Ana Pérez']['has_email']);
        $this->assertNotNull($rows['Ana Pérez']['notified_at']);
        $this->assertFalse($rows['Eva Pérez']['has_email']);
        $this->assertNull($rows['Eva Pérez']['notified_at']);
    }

    public function test_republishing_without_changes_only_mails_the_new_players(): void
    {
        Mail::fake();
        $d = $this->setUpMatch();
        $url = "/api/matches/{$d['match']->id}/callup";
        $this->callUp($d, ['Ana']);
        $this->withClubToken($this->token())->postJson("{$url}/publish")->assertOk();

        $this->travel(1)->minutes();
        $this->callUp($d, ['Ana', 'Bea']);
        $this->withClubToken($this->token())->postJson("{$url}/publish")->assertOk();

        $this->assertCount(1, $this->sentTo('mama@example.test'));
        $this->assertCount(1, $this->sentTo('papa.bea@example.test'));
    }

    public function test_republishing_after_a_change_mails_everyone_that_there_were_changes(): void
    {
        Mail::fake();
        $d = $this->setUpMatch();
        $url = "/api/matches/{$d['match']->id}/callup";
        $this->callUp($d, ['Ana', 'Bea']);
        $this->withClubToken($this->token())->postJson("{$url}/publish")->assertOk();

        $this->travel(1)->minutes();
        $this->withClubToken($this->token())->patchJson("/api/matches/{$d['match']->id}", ['venue' => 'Cancha 2'])->assertOk();
        $this->withClubToken($this->token())->postJson("{$url}/publish")->assertOk();

        Mail::assertSentCount(4);
        $updated = $this->sentTo('mama@example.test')[1];
        $this->assertSame(CallupNoticeMode::Updated, $updated->mode);
        $this->assertStringStartsWith('Hubo cambios: ', $updated->subjectLine);
        $this->assertStringContainsString('Lugar: Cancha 2', implode("\n", $updated->summary));
        $this->assertSame(CallupNoticeMode::Updated, $this->sentTo('papa.bea@example.test')[1]->mode);
    }

    public function test_the_reminder_only_goes_to_players_who_have_not_answered(): void
    {
        Mail::fake();
        $d = $this->setUpMatch();
        $url = "/api/matches/{$d['match']->id}/callup";
        $this->callUp($d, ['Ana', 'Dani', 'Bea']);

        $this->withClubToken($this->token())->postJson("{$url}/remind")
            ->assertUnprocessable()->assertJsonValidationErrors(['callup' => 'Publicá la convocatoria antes de mandar un recordatorio.']);
        Mail::assertNothingSent();

        $this->withClubToken($this->token())->postJson("{$url}/publish")->assertOk();
        $this->inTenant($this->tenantA(), fn () => MatchCallup::where('player_id', $d['Ana']->id)->update(['response' => MatchCallup::GOING]));
        $this->inTenant($this->tenantA(), fn () => MatchCallup::where('player_id', $d['Bea']->id)->update(['response' => MatchCallup::NOT_GOING]));
        $this->travel(1)->days();

        $this->withClubToken($this->token())->postJson("{$url}/remind")->assertOk();

        // Solo Dani sigue pendiente: su madre recibe el recordatorio con el link de Dani, y nada más.
        $reminder = $this->sentTo('mama@example.test')[1];
        $this->assertSame(CallupNoticeMode::Reminder, $reminder->mode);
        $this->assertSame(['Dani'], array_column($reminder->players, 'name'));
        $this->assertCount(1, $this->sentTo('papa.bea@example.test'));
    }

    public function test_the_reminder_respects_permissions_scope_and_club(): void
    {
        Mail::fake();
        $d = $this->setUpMatch();
        $url = "/api/matches/{$d['match']->id}/callup";
        $this->postJson("{$url}/remind")->assertUnauthorized();
        $this->callUp($d, ['Ana']);
        $this->withClubToken($this->token())->postJson("{$url}/publish")->assertOk();
        Mail::assertSentCount(1);

        // El utilero ve la convocatoria pero no la publica.
        $this->withClubToken($this->token('inventory_manager'))->postJson("{$url}/remind")->assertForbidden();
        // Un profe sin esa categoría no la ve (fuera de alcance: 404, como el resto del club).
        $this->withClubToken($this->token('coach'))->postJson("{$url}/remind")->assertNotFound();
        $this->withClubToken($this->clubToken($this->tenantB(), roles: ['admin']))->postJson("{$url}/remind")->assertNotFound();

        Mail::assertSentCount(1);
    }

    public function test_sending_twice_the_same_notice_mails_once(): void
    {
        Mail::fake();
        $d = $this->setUpMatch();
        $url = "/api/matches/{$d['match']->id}/callup";
        $this->callUp($d, ['Bea']);
        $this->withClubToken($this->token())->postJson("{$url}/publish")->assertOk();
        $callupIds = $this->inTenant($this->tenantA(), fn () => MatchCallup::pluck('id')->all());

        // Un aviso nuevo, procesado dos veces (reintento o entrega duplicada de la cola): sale una vez.
        $this->travel(1)->hours();
        $requestedAt = now()->toIso8601String();
        SendCallupNotice::dispatchSync($this->tenantA()->id, $d['match']->id, $callupIds, CallupNoticeMode::Updated, $requestedAt);
        SendCallupNotice::dispatchSync($this->tenantA()->id, $d['match']->id, $callupIds, CallupNoticeMode::Updated, $requestedAt);
        NotifyCallup::dispatchSync($this->tenantA()->id, $d['match']->id, CallupNoticeMode::Updated, $requestedAt);

        $this->assertCount(2, $this->sentTo('papa.bea@example.test'));
        $this->assertNull(config('database.connections.tenant.database'));
    }

    public function test_the_notice_works_on_the_club_of_the_job(): void
    {
        Mail::fake();
        $d = $this->setUpMatch();
        $this->callUp($d, ['Bea']);
        $this->withClubToken($this->token())->postJson("/api/matches/{$d['match']->id}/callup/publish")->assertOk();
        Mail::assertSentCount(1);

        // El club B no tiene ese partido: el aviso no lee la base de A.
        NotifyCallup::dispatchSync($this->tenantB()->id, $d['match']->id, CallupNoticeMode::Updated, now()->toIso8601String());

        Mail::assertSentCount(1);
    }

    public function test_a_failed_notice_is_logged_without_personal_data(): void
    {
        Log::spy();

        (new SendCallupNotice($this->tenantA()->id, 10, [1, 2], CallupNoticeMode::Published, now()->toIso8601String()))
            ->failed(new RuntimeException('Invalid address: mama@example.test'));

        Log::shouldHaveReceived('error')->once()->withArgs(function (string $message, array $context) {
            $this->assertSame(['tenant_id' => $this->tenantA()->id, 'match_id' => 10, 'callup_ids' => [1, 2], 'error' => RuntimeException::class], $context);
            $this->assertStringNotContainsString('@', $message.json_encode($context));

            return true;
        });
    }
}
