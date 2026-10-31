<?php

namespace Tests\Feature\Club;

use App\Models\Club\Category;
use App\Models\Club\ClubUser;
use App\Models\Club\Coach;
use App\Models\Club\Competition;
use App\Models\Club\CompetitionRequirement;
use App\Models\Club\Player;
use App\Models\Club\PlayerCategoryMembership;
use App\Models\Club\PlayerRequirement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** Habilitación de temporada (TIZ-32). */
class EligibilityApiTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function token(string $role = 'admin'): string
    {
        return $this->clubToken($this->tenantA(), roles: [$role]);
    }

    /**
     * Sub-15 juega la Liga Metropolitana, que pide apto (archivo, 12 meses), DNI y autorización del
     * tutor (solo menores). Ana tiene 14 años y Beto 19.
     */
    private function setUpLeague(): array
    {
        return $this->inTenant($this->tenantA(), function () {
            $category = Category::factory()->create(['name' => 'Sub-15']);
            $other = Category::factory()->create(['name' => 'Sub-17']);
            $competition = Competition::factory()->create(['sport_id' => $category->sport_id, 'name' => 'Liga Metropolitana', 'registration_deadline' => now()->addDays(10)->toDateString()]);
            $competition->categories()->attach($category->id);
            $req = fn (string $name, array $extra = []) => CompetitionRequirement::create(['competition_id' => $competition->id, 'name' => $name, ...$extra]);

            $ana = Player::factory()->create(['first_name' => 'Ana', 'last_name' => 'Acosta', 'birth_date' => now()->subYears(14)->toDateString()]);
            $beto = Player::factory()->create(['first_name' => 'Beto', 'last_name' => 'Benítez', 'birth_date' => now()->subYears(19)->toDateString()]);
            foreach ([$ana, $beto] as $i => $player) {
                PlayerCategoryMembership::create(['player_id' => $player->id, 'category_id' => $category->id, 'start_date' => '2026-03-01', 'shirt_number' => $i + 4, 'status' => 'active']);
            }

            return [
                'category' => $category, 'other' => $other, 'ana' => $ana, 'beto' => $beto,
                'apto' => $req('Apto médico', ['validity_months' => 12, 'requires_file' => true, 'sort' => 0]),
                'dni' => $req('DNI', ['sort' => 1]),
                'tutor' => $req('Autorización del tutor', ['minors_only' => true, 'sort' => 2]),
            ];
        });
    }

    private function upload(string $token, Player $player): int
    {
        return $this->withClubToken($token)
            ->post("/api/players/{$player->id}/documents", ['kind' => 'medical', 'file' => UploadedFile::fake()->create('apto.pdf', 100, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertCreated()->json('data.id');
    }

    public function test_grid_shows_what_each_player_is_missing(): void
    {
        $l = $this->setUpLeague();

        $this->withClubToken($this->token())->getJson("/api/categories/{$l['category']->id}/eligibility")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.player_name', 'Ana Acosta')
            ->assertJsonPath('data.0.is_eligible', false)
            ->assertJsonPath('data.0.missing', 3)
            ->assertJsonPath('data.0.requirements.0.name', 'Apto médico')
            ->assertJsonPath('data.0.requirements.0.status', 'pending')
            // Beto es mayor: la autorización del tutor no le aplica.
            ->assertJsonPath('data.1.missing', 2)
            ->assertJsonPath('data.1.requirements.2.status', 'not_applicable');
    }

    public function test_player_becomes_eligible_after_approving_everything(): void
    {
        $l = $this->setUpLeague();
        $token = $this->token();
        $base = "/api/players/{$l['beto']->id}/requirements";

        // El apto pide archivo.
        $this->withClubToken($token)->postJson("{$base}/{$l['apto']->id}/submit")
            ->assertUnprocessable()->assertJsonValidationErrors(['document_id' => '"Apto médico" pide un archivo: subilo y mandá su document_id.']);

        $document = $this->upload($token, $l['beto']);
        $this->withClubToken($token)->postJson("{$base}/{$l['apto']->id}/submit", ['document_id' => $document])
            ->assertOk()->assertJsonPath('data.requirements.0.status', 'submitted')->assertJsonPath('data.requirements.0.document_id', $document);

        $this->withClubToken($token)->postJson("{$base}/{$l['apto']->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.requirements.0.status', 'approved')
            ->assertJsonPath('data.requirements.0.expires_at', today()->addMonths(12)->toDateString())
            ->assertJsonPath('data.is_eligible', false);

        // El DNI se aprueba sin presentar antes: el club lo vio en mano.
        $this->withClubToken($token)->postJson("{$base}/{$l['dni']->id}/approve")
            ->assertOk()->assertJsonPath('data.is_eligible', true)->assertJsonPath('data.missing', 0);

        $this->withClubToken($token)->getJson('/api/eligibility')
            ->assertOk()
            ->assertJsonPath('data.0.category_name', 'Sub-15')
            ->assertJsonPath('data.0.players', 2)
            ->assertJsonPath('data.0.eligible', 1)
            ->assertJsonPath('data.0.days_to_deadline', 10);
    }

    public function test_an_expired_requirement_makes_the_player_ineligible(): void
    {
        $l = $this->setUpLeague();
        $token = $this->token();
        $document = $this->upload($token, $l['beto']);
        $base = "/api/players/{$l['beto']->id}/requirements";
        $this->withClubToken($token)->postJson("{$base}/{$l['apto']->id}/approve", ['document_id' => $document, 'expires_at' => now()->addDays(20)->toDateString()])
            ->assertJsonPath('data.requirements.0.expiring_soon', true);
        $this->withClubToken($token)->postJson("{$base}/{$l['dni']->id}/approve")->assertJsonPath('data.is_eligible', true);

        $this->travel(21)->days();
        // El token de sesión vence antes: uno nuevo.
        $token = $this->token();

        $this->withClubToken($token)->getJson("/api/players/{$l['beto']->id}/eligibility")
            ->assertJsonPath('data.is_eligible', false)
            ->assertJsonPath('data.requirements.0.status', 'expired');

        $this->artisan('club:expire-requirements', ['--tenant' => $this->tenantA()->slug])->assertSuccessful();
        $this->assertSame('expired', $this->inTenant($this->tenantA(), fn () => PlayerRequirement::query()->where('competition_requirement_id', $l['apto']->id)->value('status')));
    }

    public function test_reject_requires_a_reason_and_shows_it(): void
    {
        $l = $this->setUpLeague();
        $url = "/api/players/{$l['ana']->id}/requirements/{$l['tutor']->id}/reject";

        $this->withClubToken($this->token())->postJson($url)
            ->assertUnprocessable()->assertJsonValidationErrors(['reason' => 'Contá por qué se rechaza.']);
        $this->withClubToken($this->token())->postJson($url, ['reason' => 'Falta la firma'])
            ->assertOk()
            ->assertJsonPath('data.requirements.2.status', 'rejected')
            ->assertJsonPath('data.requirements.2.rejection_reason', 'Falta la firma');
    }

    public function test_rules_for_requirements_and_documents(): void
    {
        $l = $this->setUpLeague();
        $token = $this->token();

        // A un mayor no se le aprueba la autorización del tutor.
        $this->withClubToken($token)->postJson("/api/players/{$l['beto']->id}/requirements/{$l['tutor']->id}/approve")
            ->assertUnprocessable()->assertJsonValidationErrors(['requirement' => 'Este requisito es solo para menores de 18.']);

        // Un documento de otro jugador no sirve.
        $anaDocument = $this->upload($token, $l['ana']);
        $this->withClubToken($token)->postJson("/api/players/{$l['beto']->id}/requirements/{$l['apto']->id}/submit", ['document_id' => $anaDocument])
            ->assertUnprocessable()->assertJsonValidationErrors(['document_id' => 'El documento no es de este jugador.']);

        // Un requisito de una competencia que su categoría no juega: 404.
        $foreign = $this->inTenant($this->tenantA(), function () use ($l) {
            $competition = Competition::factory()->create();
            $competition->categories()->attach($l['other']->id);

            return CompetitionRequirement::create(['competition_id' => $competition->id, 'name' => 'Seguro']);
        });
        $this->withClubToken($token)->postJson("/api/players/{$l['beto']->id}/requirements/{$foreign->id}/approve")->assertNotFound();
    }

    public function test_coach_uploads_and_submits_but_cannot_approve(): void
    {
        $l = $this->setUpLeague();
        $user = User::factory()->create();
        $token = $this->clubToken($this->tenantA(), $user, ['coach']);
        $this->inTenant($this->tenantA(), function () use ($user, $l) {
            $coach = Coach::factory()->create(['club_user_id' => ClubUser::query()->where('global_user_id', $user->id)->sole()->id]);
            DB::connection('tenant')->table('category_coaches')->insert(['category_id' => $l['category']->id, 'coach_id' => $coach->id, 'assignment_type' => 'head_coach']);
        });
        $base = "/api/players/{$l['ana']->id}/requirements";

        $document = $this->upload($token, $l['ana']);
        $this->withClubToken($token)->postJson("{$base}/{$l['apto']->id}/submit", ['document_id' => $document])->assertOk();
        $this->withClubToken($token)->postJson("{$base}/{$l['apto']->id}/approve")->assertForbidden();
        $this->withClubToken($token)->getJson('/api/eligibility')->assertJsonCount(1, 'data');
        $this->withClubToken($token)->getJson("/api/categories/{$l['other']->id}/eligibility")->assertNotFound();

        // El utilero no ve la habilitación.
        $this->withClubToken($this->token('inventory_manager'))->getJson('/api/eligibility')->assertForbidden();
    }

    public function test_eligibility_is_isolated_between_clubs(): void
    {
        $l = $this->setUpLeague();
        $tokenB = $this->clubToken($this->tenantB(), roles: ['admin']);

        $this->withClubToken($tokenB)->getJson("/api/categories/{$l['category']->id}/eligibility")->assertNotFound();
        $this->withClubToken($tokenB)->postJson("/api/players/{$l['ana']->id}/requirements/{$l['dni']->id}/approve")->assertNotFound();
        $this->withClubToken($tokenB)->getJson('/api/eligibility')->assertOk()->assertJsonCount(0, 'data');
    }
}
