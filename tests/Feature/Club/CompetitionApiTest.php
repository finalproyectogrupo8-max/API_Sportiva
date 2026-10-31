<?php

namespace Tests\Feature\Club;

use App\Models\Club\Category;
use App\Models\Club\ClubUser;
use App\Models\Club\Coach;
use App\Models\Club\Competition;
use App\Models\Club\Season;
use App\Models\Club\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** Competencias y requisitos de habilitación (TIZ-31). */
class CompetitionApiTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    private function token(string $role = 'admin'): string
    {
        return $this->clubToken($this->tenantA(), roles: [$role]);
    }

    /** @return array{basquet: Sport, futbol: Sport, season: Season, next: Season, u15: Category, u17: Category, futbolU15: Category} */
    private function catalog(): array
    {
        return $this->inTenant($this->tenantA(), function () {
            $basquet = Sport::factory()->create(['name' => 'Básquet']);
            $futbol = Sport::factory()->create(['name' => 'Fútbol']);
            $season = Season::factory()->create(['name' => '2027']);

            return [
                'basquet' => $basquet, 'futbol' => $futbol, 'season' => $season,
                'next' => Season::factory()->create(['name' => '2028']),
                'u15' => Category::factory()->create(['sport_id' => $basquet->id, 'season_id' => $season->id, 'name' => 'Sub-15']),
                'u17' => Category::factory()->create(['sport_id' => $basquet->id, 'season_id' => $season->id, 'name' => 'Sub-17']),
                'futbolU15' => Category::factory()->create(['sport_id' => $futbol->id, 'season_id' => $season->id, 'name' => 'Fútbol Sub-15']),
            ];
        });
    }

    private function payload(array $c, array $overrides = []): array
    {
        return [
            'sport_id' => $c['basquet']->id,
            'season_id' => $c['season']->id,
            'name' => 'Liga Metropolitana de Básquet',
            'organizer' => 'Federación Metropolitana',
            'registration_deadline' => now()->addDays(12)->toDateString(),
            'category_ids' => [$c['u15']->id, $c['u17']->id],
            'requirements' => [
                ['name' => 'Ficha federativa', 'requires_file' => true],
                ['name' => 'Apto médico', 'validity_months' => 12, 'requires_file' => true],
                ['name' => 'DNI', 'requires_file' => true],
                ['name' => 'Autorización del tutor', 'minors_only' => true],
            ],
            ...$overrides,
        ];
    }

    public function test_creates_a_competition_with_requirements_and_categories(): void
    {
        $c = $this->catalog();

        $this->withClubToken($this->token())->postJson('/api/competitions', $this->payload($c))
            ->assertCreated()
            ->assertJsonPath('data.days_to_deadline', 12)
            ->assertJsonCount(2, 'data.categories')
            ->assertJsonCount(4, 'data.requirements')
            ->assertJsonPath('data.requirements.1.name', 'Apto médico')
            ->assertJsonPath('data.requirements.1.validity_months', 12)
            ->assertJsonPath('data.requirements.3.minors_only', true)
            ->assertJsonPath('data.requirements.3.is_required', true);
    }

    public function test_categories_must_be_of_the_same_sport(): void
    {
        $c = $this->catalog();

        $this->withClubToken($this->token())->postJson('/api/competitions', $this->payload($c, ['category_ids' => [$c['u15']->id, $c['futbolU15']->id]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_ids' => 'Estas categorías son de otro deporte: Fútbol Sub-15.']);
        $this->assertSame(0, $this->inTenant($this->tenantA(), fn () => Competition::query()->count()));

        $this->withClubToken($this->token())->postJson('/api/competitions', ['requirements' => [['validity_months' => 0]]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sport_id', 'season_id', 'name', 'requirements.0.name', 'requirements.0.validity_months']);
    }

    public function test_updates_requirements_as_a_list(): void
    {
        $c = $this->catalog();
        $token = $this->token();
        $created = $this->withClubToken($token)->postJson('/api/competitions', $this->payload($c))->json('data');
        [$ficha, $apto] = $created['requirements'];

        // Se edita el apto, se borra la ficha y el DNI, y se agrega el seguro.
        $this->withClubToken($token)->patchJson("/api/competitions/{$created['id']}", ['requirements' => [
            ['id' => $apto['id'], 'name' => 'Apto médico', 'validity_months' => 6, 'requires_file' => true],
            ['name' => 'Seguro deportivo'],
        ], 'category_ids' => [$c['u15']->id]])
            ->assertOk()
            ->assertJsonCount(2, 'data.requirements')
            ->assertJsonPath('data.requirements.0.id', $apto['id'])
            ->assertJsonPath('data.requirements.0.validity_months', 6)
            ->assertJsonPath('data.requirements.1.name', 'Seguro deportivo')
            ->assertJsonCount(1, 'data.categories')
            ->assertJsonPath('data.name', 'Liga Metropolitana de Básquet');

        // Sin requirements en el body, la lista no se toca.
        $this->withClubToken($token)->patchJson("/api/competitions/{$created['id']}", ['organizer' => 'FeBAMBA'])
            ->assertOk()->assertJsonCount(2, 'data.requirements');

        // Un requisito de otra competencia no se puede editar por id.
        $this->withClubToken($token)->patchJson("/api/competitions/{$created['id']}", ['requirements' => [['id' => $ficha['id'] + 999, 'name' => 'X']]])
            ->assertUnprocessable()->assertJsonValidationErrors('requirements.0.id');
    }

    public function test_copies_to_next_season_without_categories(): void
    {
        $c = $this->catalog();
        $token = $this->token();
        $id = $this->withClubToken($token)->postJson('/api/competitions', $this->payload($c))->json('data.id');

        $this->withClubToken($token)->postJson("/api/competitions/{$id}/copy", ['season_id' => $c['next']->id, 'name' => 'Liga Metropolitana 2028'])
            ->assertCreated()
            ->assertJsonPath('data.season_id', $c['next']->id)
            ->assertJsonPath('data.name', 'Liga Metropolitana 2028')
            ->assertJsonCount(0, 'data.categories')
            ->assertJsonCount(4, 'data.requirements')
            ->assertJsonPath('data.requirements.1.validity_months', 12);
    }

    public function test_lists_with_filters_and_deletes(): void
    {
        $c = $this->catalog();
        $token = $this->token();
        $id = $this->withClubToken($token)->postJson('/api/competitions', $this->payload($c))->json('data.id');
        $this->withClubToken($token)->postJson('/api/competitions', $this->payload($c, ['sport_id' => $c['futbol']->id, 'name' => 'Liga de Fútbol', 'category_ids' => [$c['futbolU15']->id]]))->assertCreated();

        $this->withClubToken($token)->getJson('/api/competitions')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('meta.total', 2);
        $this->withClubToken($token)->getJson("/api/competitions?sport_id={$c['futbol']->id}")->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Liga de Fútbol');
        $this->withClubToken($token)->getJson("/api/competitions?category_id={$c['u17']->id}")->assertJsonCount(1, 'data');

        $this->withClubToken($token)->deleteJson("/api/competitions/{$id}")->assertNoContent();
        $this->withClubToken($token)->getJson("/api/competitions/{$id}")->assertNotFound();
    }

    public function test_coach_sees_only_competitions_of_own_categories(): void
    {
        $c = $this->catalog();
        $admin = $this->token();
        $basquet = $this->withClubToken($admin)->postJson('/api/competitions', $this->payload($c))->json('data.id');
        $futbol = $this->withClubToken($admin)->postJson('/api/competitions', $this->payload($c, ['sport_id' => $c['futbol']->id, 'name' => 'Liga de Fútbol', 'category_ids' => [$c['futbolU15']->id]]))->json('data.id');

        $user = User::factory()->create();
        $token = $this->clubToken($this->tenantA(), $user, ['coach']);
        $this->inTenant($this->tenantA(), function () use ($user, $c) {
            $coach = Coach::factory()->create(['club_user_id' => ClubUser::query()->where('global_user_id', $user->id)->sole()->id]);
            DB::connection('tenant')->table('category_coaches')->insert(['category_id' => $c['futbolU15']->id, 'coach_id' => $coach->id, 'assignment_type' => 'head_coach']);
        });

        $this->withClubToken($token)->getJson('/api/competitions')->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Liga de Fútbol');
        $this->withClubToken($token)->getJson("/api/competitions/{$futbol}")->assertOk();
        $this->withClubToken($token)->getJson("/api/competitions/{$basquet}")->assertNotFound();
        $this->withClubToken($token)->postJson('/api/competitions', $this->payload($c))->assertForbidden();
    }

    public function test_requires_token_and_isolates_clubs(): void
    {
        $this->getJson('/api/competitions')->assertUnauthorized();
        $this->withClubToken($this->token('inventory_manager'))->getJson('/api/competitions')->assertForbidden()->assertJsonPath('code', 'forbidden');

        $competitionB = $this->inTenant($this->tenantB(), fn () => Competition::factory()->count(2)->create()->last());
        $token = $this->token();
        $this->withClubToken($token)->getJson("/api/competitions/{$competitionB->id}")->assertNotFound();
        $this->withClubToken($token)->patchJson("/api/competitions/{$competitionB->id}", ['name' => 'X'])->assertNotFound();
        $this->withClubToken($token)->deleteJson("/api/competitions/{$competitionB->id}")->assertNotFound();
    }

    public function test_a_match_can_belong_to_a_competition(): void
    {
        $c = $this->catalog();
        $token = $this->token();
        $id = $this->withClubToken($token)->postJson('/api/competitions', $this->payload($c))->json('data.id');

        $this->withClubToken($token)->postJson('/api/matches', [
            'category_id' => $c['u15']->id, 'competition_id' => $id, 'opponent' => 'Rival', 'match_date' => '2027-04-10 10:00', 'is_home' => true,
        ])->assertCreated()->assertJsonPath('data.competition_id', $id);
        $this->withClubToken($token)->postJson('/api/matches', [
            'category_id' => $c['u15']->id, 'competition_id' => 999999, 'opponent' => 'Rival', 'match_date' => '2027-04-10 10:00', 'is_home' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('competition_id');
    }
}
