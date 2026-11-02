<?php

namespace Tests\Feature\Club;

use App\Mail\AnnouncementMail;
use App\Models\Club\Announcement;
use App\Models\Club\Category;
use App\Models\Club\CategoryCoach;
use App\Models\Club\ClubUser;
use App\Models\Club\Coach;
use App\Models\Club\Player;
use App\Models\Club\PlayerCategoryMembership;
use App\Models\Club\PlayerContact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** Comunicados por mail del club (TIZ-24). */
class AnnouncementApiTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    /**
     * Sub-15 con Ana y Dani (hermanas: la misma madre, con el email en otra capitalización) y Eva,
     * que no tiene tutor con email. Ana tiene su propio email (`self`). La profe Marta dirige Sub-15.
     */
    private function setUpClub(): array
    {
        return $this->inTenant($this->tenantA(), function () {
            $sub15 = Category::factory()->create(['name' => 'Sub-15']);
            $sub17 = Category::factory()->create(['name' => 'Sub-17']);
            $players = [];
            foreach (['Ana' => 'mama@example.test', 'Dani' => ' MAMA@example.test ', 'Eva' => null] as $name => $email) {
                $player = Player::factory()->create(['first_name' => $name, 'last_name' => 'Pérez']);
                PlayerCategoryMembership::create(['player_id' => $player->id, 'category_id' => $sub15->id, 'start_date' => '2026-03-01', 'status' => 'active']);
                PlayerContact::create(['player_id' => $player->id, 'name' => "Tutor de {$name}", 'relationship' => 'mother', 'phone' => '1155550000', 'email' => $email, 'is_primary' => true]);
                $players[$name] = $player;
            }
            PlayerContact::create(['player_id' => $players['Ana']->id, 'name' => 'Ana Pérez', 'relationship' => 'self', 'email' => 'ana@example.test']);
            $marta = Coach::create(['first_name' => 'Marta', 'last_name' => 'Gómez', 'email' => 'marta@example.test', 'status' => 'active']);
            CategoryCoach::create(['category_id' => $sub15->id, 'coach_id' => $marta->id, 'assignment_type' => 'head_coach']);

            return ['sub15' => $sub15, 'sub17' => $sub17, 'marta' => $marta, ...$players];
        });
    }

    /** Token de alguien con email propio, para el Reply-To. */
    private function token(string $role = 'admin', string $email = 'coordinacion@club.test'): string
    {
        return $this->clubToken($this->tenantA(), User::factory()->create(['email' => $email]), [$role]);
    }

    public function test_a_coordinator_drafts_previews_and_sends_one_mail_per_address(): void
    {
        Mail::fake();
        $d = $this->setUpClub();
        $token = $this->token();
        $audience = ['categories' => [['category_id' => $d['sub15']->id, 'players' => true, 'tutors' => true, 'coaches' => true]]];

        $this->withClubToken($token)->postJson('/api/announcements', ['subject' => 'Sin asunto', 'body' => 'x', 'audience' => ['categories' => []]])
            ->assertUnprocessable()->assertJsonValidationErrors('audience');

        $id = $this->withClubToken($token)->postJson('/api/announcements', [
            'subject' => 'Se suspende el entrenamiento', 'body' => "Por lluvia, **no** hay entrenamiento.\n\n<script>alert(1)</script>", 'audience' => $audience,
        ])->assertCreated()->assertJsonPath('data.status', 'draft')->json('data.id');

        // Ana (self), la madre de Ana y Dani (una sola dirección) y la profe. Eva no tiene tutor con email.
        $this->withClubToken($token)->postJson('/api/announcements/preview-audience', ['audience' => $audience])
            ->assertOk()
            ->assertJsonPath('data.recipients_count', 3)
            ->assertJsonPath('data.without_email_count', 3)
            ->assertJsonPath('data.without_email', ['Dani Pérez', 'Eva Pérez', 'Tutores de Eva Pérez']);
        Mail::assertNothingSent();

        $this->withClubToken($token)->postJson("/api/announcements/{$id}/send")
            ->assertStatus(202)->assertJsonPath('data.recipients_count', 3);

        Mail::assertSentCount(3);
        Mail::assertSent(AnnouncementMail::class, fn (AnnouncementMail $m) => $m->hasTo('mama@example.test')
            && $m->hasReplyTo('coordinacion@club.test')
            && str_ends_with($m->envelope()->subject, '] Se suspende el entrenamiento'));
        $html = Mail::sent(AnnouncementMail::class)->first()->render();
        $this->assertMatchesRegularExpression('#<strong[^>]*>no</strong>#', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);

        $this->withClubToken($token)->getJson("/api/announcements/{$id}")
            ->assertJsonPath('data.status', 'sent')->assertJsonPath('data.sent_count', 3)->assertJsonPath('data.failed_count', 0);
        $this->withClubToken($token)->getJson("/api/announcements/{$id}/recipients")
            ->assertOk()->assertJsonCount(3, 'data')->assertJsonPath('data.0.status', 'sent');

        // Enviado: no se edita, no se borra ni se vuelve a mandar.
        $this->withClubToken($token)->patchJson("/api/announcements/{$id}", ['subject' => 'Otro'])->assertConflict();
        $this->withClubToken($token)->deleteJson("/api/announcements/{$id}")->assertConflict();
        $this->withClubToken($token)->postJson("/api/announcements/{$id}/send")->assertConflict();
        Mail::assertSentCount(3);
    }

    public function test_an_audience_without_emails_cannot_be_sent(): void
    {
        Mail::fake();
        $d = $this->setUpClub();
        $token = $this->token();

        $id = $this->withClubToken($token)->postJson('/api/announcements', [
            'subject' => 'Aviso', 'body' => 'Hola', 'audience' => ['players' => ['player_ids' => [$d['Eva']->id], 'tutors' => true]],
        ])->assertCreated()->json('data.id');

        $this->withClubToken($token)->postJson("/api/announcements/{$id}/send")->assertUnprocessable()->assertJsonValidationErrors('audience');
        $this->withClubToken($token)->getJson("/api/announcements/{$id}")->assertJsonPath('data.status', 'draft');
    }

    public function test_a_coach_only_writes_to_own_categories_and_sees_own_announcements(): void
    {
        Mail::fake();
        $d = $this->setUpClub();
        $coachUser = User::factory()->create(['email' => 'marta@example.test']);
        $token = $this->clubToken($this->tenantA(), $coachUser, ['coach']);
        $this->inTenant($this->tenantA(), fn () => $d['marta']->update(['club_user_id' => ClubUser::where('global_user_id', $coachUser->id)->value('id')]));
        $other = $this->withClubToken($this->token())->postJson('/api/announcements', [
            'subject' => 'Del coordinador', 'body' => 'x', 'audience' => ['categories' => [['category_id' => $d['sub17']->id, 'players' => true]]],
        ])->json('data.id');

        $this->withClubToken($token)->postJson('/api/announcements', [
            'subject' => 'Ajena', 'body' => 'x', 'audience' => ['categories' => [['category_id' => $d['sub17']->id, 'tutors' => true]]],
        ])->assertForbidden();
        $this->withClubToken($token)->postJson('/api/announcements', [
            'subject' => 'Al staff', 'body' => 'x', 'audience' => ['role_slugs' => ['admin']],
        ])->assertForbidden();

        $id = $this->withClubToken($token)->postJson('/api/announcements', [
            'subject' => 'Partido del sábado', 'body' => 'Nos vemos.', 'audience' => ['categories' => [['category_id' => $d['sub15']->id, 'tutors' => true]]],
        ])->assertCreated()->json('data.id');
        $this->withClubToken($token)->postJson("/api/announcements/{$id}/send")->assertStatus(202);
        Mail::assertSentCount(1);

        // Ve solo los suyos.
        $this->withClubToken($token)->getJson('/api/announcements')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $id);
        $this->withClubToken($token)->getJson("/api/announcements/{$other}")->assertNotFound();
    }

    public function test_permissions_and_isolation(): void
    {
        $d = $this->setUpClub();
        $author = User::factory()->create();
        $id = $this->inTenant($this->tenantA(), fn () => Announcement::create([
            'subject' => 'A', 'body' => 'x', 'audience' => ['categories' => [['category_id' => $d['sub15']->id, 'players' => true]]],
            'status' => 'draft', 'created_by' => ClubUser::create(['global_user_id' => $author->id, 'display_name' => 'Autor', 'status' => 'active'])->id,
        ])->id);

        $this->getJson('/api/announcements')->assertUnauthorized();
        $this->withClubToken($this->token('maintenance_manager', 'mant@club.test'))->getJson('/api/announcements')->assertForbidden();
        $this->withClubToken($this->clubToken($this->tenantB(), roles: ['admin']))->getJson("/api/announcements/{$id}")->assertNotFound();
    }
}
