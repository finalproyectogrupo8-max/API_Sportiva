<?php

namespace Tests\Feature\Club;

use App\Models\Club\Category;
use App\Models\Club\ClubUser;
use App\Models\Club\Coach;
use App\Models\Club\Document;
use App\Models\Club\Player;
use App\Models\Club\PlayerCategoryMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** Documentos de jugadores y escudo del club (TIZ-29). */
class DocumentApiTest extends TestCase
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

    private function pdf(string $name = 'apto.pdf'): UploadedFile
    {
        return UploadedFile::fake()->create($name, 200, 'application/pdf');
    }

    public function test_uploads_lists_downloads_and_deletes_a_document(): void
    {
        $player = $this->inTenant($this->tenantA(), fn () => Player::factory()->create());
        $token = $this->token();

        $doc = $this->withClubToken($token)
            ->post("/api/players/{$player->id}/documents", ['kind' => 'medical', 'file' => $this->pdf()], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.kind', 'medical')
            ->assertJsonPath('data.original_name', 'apto.pdf')
            ->json('data');

        $path = $this->inTenant($this->tenantA(), fn () => Document::find($doc['id'])->path);
        $this->assertStringStartsWith('clubs/'.$this->tenantA()->slug."/player/{$player->id}/", $path);
        Storage::disk('local')->assertExists($path);

        $this->withClubToken($token)->getJson("/api/players/{$player->id}/documents")->assertOk()->assertJsonCount(1, 'data');
        $this->withClubToken($token)->get("/api/documents/{$doc['id']}/download")->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertDatabaseHas('audit_logs', ['action' => 'document.downloaded', 'subject_id' => $doc['id']]);

        $this->withClubToken($token)->deleteJson("/api/documents/{$doc['id']}")->assertNoContent();
        Storage::disk('local')->assertMissing($path);
        $this->withClubToken($token)->getJson("/api/players/{$player->id}/documents")->assertJsonCount(0, 'data');
    }

    public function test_validates_type_and_size(): void
    {
        $player = $this->inTenant($this->tenantA(), fn () => Player::factory()->create());
        $token = $this->token();
        $url = "/api/players/{$player->id}/documents";

        $this->withClubToken($token)->post($url, ['kind' => 'medical', 'file' => UploadedFile::fake()->create('virus.exe', 10)], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors(['file' => 'El archivo tiene que ser PDF, JPG, PNG, WEBP o HEIC.']);
        $this->withClubToken($token)->post($url, ['kind' => 'medical', 'file' => UploadedFile::fake()->create('grande.pdf', 11000, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors(['file' => 'El archivo es demasiado grande.']);
        $this->withClubToken($token)->post($url, ['kind' => 'photo', 'file' => $this->pdf()], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->withClubToken($token)->post($url, ['kind' => 'receta', 'file' => $this->pdf()], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors('kind');
    }

    public function test_a_new_photo_replaces_the_previous_one(): void
    {
        $player = $this->inTenant($this->tenantA(), fn () => Player::factory()->create());
        $token = $this->token();
        $url = "/api/players/{$player->id}/documents";

        $first = $this->withClubToken($token)->post($url, ['kind' => 'photo', 'file' => UploadedFile::fake()->create('a.jpg', 120, 'image/jpeg')], ['Accept' => 'application/json'])->json('data.id');
        $second = $this->withClubToken($token)->post($url, ['kind' => 'photo', 'file' => UploadedFile::fake()->create('b.png', 120, 'image/png')], ['Accept' => 'application/json'])->json('data.id');

        $this->withClubToken($token)->getJson("/api/players/{$player->id}")->assertJsonPath('data.photo_document_id', $second);
        $this->withClubToken($token)->get("/api/documents/{$first}/download")->assertNotFound();
        // La foto no se audita: se muestra en todos lados.
        $this->withClubToken($token)->get("/api/documents/{$second}/download")->assertOk();
        $this->assertDatabaseMissing('audit_logs', ['action' => 'document.downloaded', 'subject_id' => $second]);
    }

    public function test_coach_reaches_only_documents_of_own_players(): void
    {
        $user = User::factory()->create();
        $token = $this->clubToken($this->tenantA(), $user, ['coach']);
        [$mine, $foreign] = $this->inTenant($this->tenantA(), function () use ($user) {
            $own = Category::factory()->create(['name' => 'U15']);
            $coach = Coach::factory()->create(['club_user_id' => ClubUser::query()->where('global_user_id', $user->id)->sole()->id]);
            DB::connection('tenant')->table('category_coaches')->insert(['category_id' => $own->id, 'coach_id' => $coach->id, 'assignment_type' => 'head_coach']);
            $mine = Player::factory()->create();
            PlayerCategoryMembership::create(['player_id' => $mine->id, 'category_id' => $own->id, 'start_date' => '2026-03-01', 'status' => 'active']);

            return [$mine, Player::factory()->create()];
        });
        $admin = $this->token();
        $foreignDoc = $this->withClubToken($admin)->post("/api/players/{$foreign->id}/documents", ['kind' => 'medical', 'file' => $this->pdf()], ['Accept' => 'application/json'])->json('data.id');

        // Sube y ve documentos de sus jugadores, pero no borra.
        $mineDoc = $this->withClubToken($token)->post("/api/players/{$mine->id}/documents", ['kind' => 'authorization', 'file' => $this->pdf('autorizacion.pdf')], ['Accept' => 'application/json'])
            ->assertCreated()->json('data.id');
        $this->withClubToken($token)->get("/api/documents/{$mineDoc}/download")->assertOk();
        $this->withClubToken($token)->deleteJson("/api/documents/{$mineDoc}")->assertForbidden();

        $this->withClubToken($token)->getJson("/api/players/{$foreign->id}/documents")->assertNotFound();
        $this->withClubToken($token)->get("/api/documents/{$foreignDoc}/download")->assertNotFound();

        // El utilero no ve documentos.
        $this->withClubToken($this->token('inventory_manager'))->get("/api/documents/{$mineDoc}/download")->assertForbidden();
    }

    public function test_documents_are_isolated_between_clubs(): void
    {
        $playerB = $this->inTenant($this->tenantB(), fn () => Player::factory()->create());
        $docB = $this->withClubToken($this->clubToken($this->tenantB(), roles: ['admin']))
            ->post("/api/players/{$playerB->id}/documents", ['kind' => 'medical', 'file' => $this->pdf()], ['Accept' => 'application/json'])
            ->json('data.id');
        $this->inTenant($this->tenantA(), fn () => Player::factory()->count(2)->create());

        $this->withClubToken($this->token())->get("/api/documents/{$docB}/download")->assertNotFound();
        $this->withClubToken($this->token())->deleteJson("/api/documents/{$docB}")->assertNotFound();
    }

    public function test_club_logo_upload_and_public_download(): void
    {
        $tenant = $this->tenantA();
        $token = $this->token();

        $this->withClubToken($this->token('coordinator'))->post('/api/club/logo', ['logo' => UploadedFile::fake()->create('escudo.png', 80, 'image/png')], ['Accept' => 'application/json'])->assertForbidden();
        $this->withClubToken($token)->post('/api/club/logo', ['logo' => $this->pdf()], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors(['logo' => 'El escudo tiene que ser PNG, JPG o WEBP.']);

        $url = $this->withClubToken($token)->post('/api/club/logo', ['logo' => UploadedFile::fake()->create('escudo.png', 80, 'image/png')], ['Accept' => 'application/json'])
            ->assertOk()->json('data.logo_url');
        $this->assertStringContainsString("/api/public/clubs/{$tenant->slug}/logo", $url);
        $this->withClubToken($token)->getJson('/api/me')->assertJsonPath('tenant.logo_url', $url);

        $this->get("/api/public/clubs/{$tenant->slug}/logo")->assertOk();
        $this->get('/api/public/clubs/no_existe/logo')->assertNotFound();

        $this->withClubToken($token)->deleteJson('/api/club/logo')->assertNoContent();
        $this->get("/api/public/clubs/{$tenant->slug}/logo")->assertNotFound();
    }
}
