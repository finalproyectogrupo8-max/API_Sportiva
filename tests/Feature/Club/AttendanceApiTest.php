<?php

namespace Tests\Feature\Club;

use App\Models\Club\Category;
use App\Models\Club\ClubUser;
use App\Models\Club\Coach;
use App\Models\Club\GameMatch;
use App\Models\Club\Player;
use App\Models\Club\PlayerAttendance;
use App\Models\Club\PlayerCategoryMembership;
use App\Models\Club\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** Asistencia, calendario y alcance del profe en partidos y entrenamientos (TIZ-16). */
class AttendanceApiTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    /** Profe de U15 con dos jugadores en el plantel y un partido y un entrenamiento de cada categoría. */
    private function setUpClub(): array
    {
        $user = User::factory()->create();
        $token = $this->clubToken($this->tenantA(), $user, ['coach']);

        $data = $this->inTenant($this->tenantA(), function () use ($user) {
            $u15 = Category::factory()->create(['name' => 'U15']);
            $u17 = Category::factory()->create(['name' => 'U17']);
            $coach = Coach::factory()->create(['club_user_id' => ClubUser::query()->where('global_user_id', $user->id)->sole()->id]);
            DB::connection('tenant')->table('category_coaches')->insert(['category_id' => $u15->id, 'coach_id' => $coach->id, 'assignment_type' => 'head_coach']);

            $ana = Player::factory()->create(['first_name' => 'Ana', 'last_name' => 'Acosta']);
            $bea = Player::factory()->create(['first_name' => 'Bea', 'last_name' => 'Benítez']);
            $other = Player::factory()->create();
            foreach ([[$ana, $u15, 4], [$bea, $u15, 7], [$other, $u17, 9]] as [$player, $category, $number]) {
                PlayerCategoryMembership::create(['player_id' => $player->id, 'category_id' => $category->id, 'start_date' => '2026-03-01', 'shirt_number' => $number, 'status' => 'active']);
            }

            return [
                'ana' => $ana, 'bea' => $bea, 'other' => $other,
                'match' => GameMatch::factory()->create(['category_id' => $u15->id, 'match_date' => '2026-11-07 10:00:00']),
                'foreignMatch' => GameMatch::factory()->create(['category_id' => $u17->id, 'match_date' => '2026-11-08 10:00:00']),
                'training' => Training::factory()->create(['category_id' => $u15->id, 'starts_at' => '2026-11-04 18:00:00', 'ends_at' => '2026-11-04 19:30:00']),
            ];
        });

        return [$token, $data];
    }

    public function test_coach_takes_attendance_and_repeating_does_not_duplicate(): void
    {
        [$token, $d] = $this->setUpClub();
        $url = "/api/matches/{$d['match']->id}/attendance";

        $this->withClubToken($token)->getJson($url)
            ->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.player_name', 'Ana Acosta')
            ->assertJsonPath('data.0.shirt_number', 4)
            ->assertJsonPath('data.0.status', null);

        $records = ['records' => [
            ['player_id' => $d['ana']->id, 'status' => 'present'],
            ['player_id' => $d['bea']->id, 'status' => 'absent', 'notes' => 'Avisó que estaba enferma'],
        ]];
        $this->withClubToken($token)->putJson($url, $records)->assertOk()->assertJsonPath('data.1.status', 'absent');

        // Corrección: el mismo PUT con otro estado actualiza, no duplica.
        $records['records'][1]['status'] = 'excused';
        $this->withClubToken($token)->putJson($url, $records)->assertOk()->assertJsonPath('data.1.status', 'excused');

        $this->assertSame(2, $this->inTenant($this->tenantA(), fn () => PlayerAttendance::query()->where('match_id', $d['match']->id)->count()));
    }

    public function test_only_players_of_the_squad_can_be_recorded(): void
    {
        [$token, $d] = $this->setUpClub();

        $this->withClubToken($token)
            ->putJson("/api/trainings/{$d['training']->id}/attendance", ['records' => [
                ['player_id' => $d['ana']->id, 'status' => 'present'],
                ['player_id' => $d['other']->id, 'status' => 'present'],
            ]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['records.1.player_id' => 'El jugador no está en el plantel de la categoría.']);

        $this->withClubToken($token)
            ->putJson("/api/trainings/{$d['training']->id}/attendance", ['records' => [['player_id' => $d['ana']->id, 'status' => 'tarde']]])
            ->assertUnprocessable()->assertJsonValidationErrors('records.0.status');
    }

    public function test_coach_only_sees_events_of_own_categories(): void
    {
        [$token, $d] = $this->setUpClub();

        $this->withClubToken($token)->getJson('/api/matches')->assertJsonCount(1, 'data');
        $this->withClubToken($token)->getJson("/api/matches/{$d['foreignMatch']->id}")->assertNotFound();
        $this->withClubToken($token)->getJson("/api/matches/{$d['foreignMatch']->id}/attendance")->assertNotFound();
        $this->withClubToken($token)->patchJson("/api/matches/{$d['match']->id}", ['our_score' => 3, 'opponent_score' => 1])->assertOk();
        // Borrar partidos no es del profe.
        $this->withClubToken($token)->deleteJson("/api/matches/{$d['match']->id}")->assertForbidden();
    }

    public function test_calendar_joins_matches_and_trainings_in_order(): void
    {
        [$token, $d] = $this->setUpClub();
        $admin = $this->clubToken($this->tenantA(), roles: ['admin']);

        $this->withClubToken($token)->getJson('/api/calendar?from=2026-11-02&to=2026-11-08')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.type', 'training')
            ->assertJsonPath('data.1.type', 'match')
            ->assertJsonPath('data.1.category_name', 'U15');

        $this->withClubToken($admin)->getJson('/api/calendar?from=2026-11-02&to=2026-11-08')->assertJsonCount(3, 'data');
        $this->withClubToken($admin)->getJson('/api/calendar?from=2026-01-01&to=2026-06-01')
            ->assertUnprocessable()->assertJsonValidationErrors(['to' => 'El rango puede ser de hasta 62 días.']);

        // El utilero ve partidos y entrenamientos; mantenimiento también. Sin ninguno de los dos, 403.
        $this->withClubToken($this->clubToken($this->tenantA(), roles: ['inventory_manager']))
            ->getJson('/api/calendar?from=2026-11-02&to=2026-11-08')->assertJsonCount(3, 'data');
    }

    public function test_trainings_crud_and_isolation(): void
    {
        [, $d] = $this->setUpClub();
        $admin = $this->clubToken($this->tenantA(), roles: ['admin']);
        $category = $d['match']->category_id;

        $id = $this->withClubToken($admin)
            ->postJson('/api/trainings', ['category_id' => $category, 'starts_at' => '2026-11-05 18:00', 'ends_at' => '2026-11-05 19:30', 'location' => 'Cancha 2'])
            ->assertCreated()->assertJsonPath('data.location', 'Cancha 2')->json('data.id');
        $this->withClubToken($admin)->postJson('/api/trainings', ['category_id' => $category, 'starts_at' => '2026-11-05 18:00', 'ends_at' => '2026-11-05 17:00'])
            ->assertUnprocessable()->assertJsonValidationErrors(['ends_at' => 'El entrenamiento tiene que terminar después de empezar.']);
        $this->withClubToken($admin)->patchJson("/api/trainings/{$id}", ['objectives' => 'Defensa'])->assertOk()->assertJsonPath('data.location', 'Cancha 2');
        $this->withClubToken($admin)->deleteJson("/api/trainings/{$id}")->assertNoContent();

        $trainingB = $this->inTenant($this->tenantB(), fn () => Training::factory()->count(3)->create()->last());
        $this->withClubToken($admin)->getJson("/api/trainings/{$trainingB->id}")->assertNotFound();
    }
}
