<?php

namespace Tests\Feature\Club;

use App\Models\Club\Category;
use App\Models\Club\CategoryCoach;
use App\Models\Club\ClubUser;
use App\Models\Club\Coach;
use App\Models\Club\Competition;
use App\Models\Club\GameMatch;
use App\Models\Club\Player;
use App\Models\Club\PlayerCategoryMembership;
use App\Models\Club\Season;
use App\Models\Club\Sport;
use App\Models\Club\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/** Evaluaciones físicas, objetivos de la temporada, tabla de posiciones e indicadores de Análisis (TIZ-20). */
class AnalysisProjectApiTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    /** @return array{season: Season, u15: Category, u17: Category, ana: Player, beto: Player, carla: Player, marta: Coach} */
    private function club(): array
    {
        return $this->inTenant($this->tenantA(), function () {
            $sport = Sport::factory()->create(['name' => 'Básquet']);
            $season = Season::factory()->create(['name' => '2026', 'status' => 'active']);
            $u15 = Category::factory()->create(['sport_id' => $sport->id, 'season_id' => $season->id, 'name' => 'Sub-15']);
            $u17 = Category::factory()->create(['sport_id' => $sport->id, 'season_id' => $season->id, 'name' => 'Sub-17']);

            $player = function (string $first, Category $category) {
                $p = Player::factory()->create(['first_name' => $first, 'last_name' => 'Test']);
                PlayerCategoryMembership::create(['player_id' => $p->id, 'category_id' => $category->id, 'start_date' => now()->subMonths(3)->toDateString(), 'status' => 'active']);

                return $p;
            };

            $marta = Coach::factory()->create(['first_name' => 'Marta', 'last_name' => 'Profe']);
            CategoryCoach::create(['category_id' => $u15->id, 'coach_id' => $marta->id, 'assignment_type' => 'head_coach']);

            return [
                'season' => $season, 'u15' => $u15, 'u17' => $u17, 'marta' => $marta,
                'ana' => $player('Ana', $u15), 'beto' => $player('Beto', $u15), 'carla' => $player('Carla', $u17),
            ];
        });
    }

    private function coachToken(Coach $coach, string $role = 'coach'): string
    {
        $user = User::factory()->create();
        $token = $this->clubToken($this->tenantA(), $user, [$role]);
        $this->inTenant($this->tenantA(), fn () => $coach->update(['club_user_id' => ClubUser::where('global_user_id', $user->id)->value('id')]));

        return $token;
    }

    private function evaluation(int $playerId, array $overrides = []): array
    {
        return [
            'player_id' => $playerId,
            'evaluated_at' => now()->subDays(3)->toDateString(),
            'summary' => 'Buen estado general',
            'measurements' => [
                ['metric_key' => 'sprint_30m', 'metric_name' => 'Sprint 30 m', 'numeric_value' => 4.8, 'unit' => 's'],
                ['metric_key' => 'postura', 'metric_name' => 'Postura', 'text_value' => 'Correcta'],
            ],
            ...$overrides,
        ];
    }

    public function test_evaluations_crud_with_measurements(): void
    {
        $c = $this->club();
        $token = $this->clubToken($this->tenantA(), roles: ['admin']);

        $this->withClubToken($token)->postJson('/api/physical-evaluations', $this->evaluation($c['ana']->id))
            ->assertUnprocessable()->assertJsonValidationErrors('evaluator_coach_id');

        $id = $this->withClubToken($token)
            ->postJson('/api/physical-evaluations', $this->evaluation($c['ana']->id, ['evaluator_coach_id' => $c['marta']->id]))
            ->assertCreated()
            ->assertJsonPath('data.category_id', $c['u15']->id)
            ->assertJsonPath('data.player_name', 'Ana Test')
            ->assertJsonPath('data.evaluator_name', 'Marta Profe')
            ->assertJsonCount(2, 'data.measurements')
            ->assertJsonPath('data.measurements.0.numeric_value', 4.8)
            ->json('data.id');

        $this->withClubToken($token)->postJson('/api/physical-evaluations', $this->evaluation($c['ana']->id, [
            'evaluator_coach_id' => $c['marta']->id,
            'measurements' => [['metric_key' => 'salto', 'metric_name' => 'Salto']],
        ]))->assertUnprocessable()->assertJsonValidationErrors('measurements.0.numeric_value');

        $this->withClubToken($token)->patchJson("/api/physical-evaluations/{$id}", [
            'measurements' => [['metric_key' => 'sprint_30m', 'metric_name' => 'Sprint 30 m', 'numeric_value' => 4.6, 'unit' => 's']],
        ])->assertOk()->assertJsonCount(1, 'data.measurements')->assertJsonPath('data.measurements.0.numeric_value', 4.6);

        $this->withClubToken($token)->getJson("/api/physical-evaluations?player_id={$c['ana']->id}&metric_key=sprint_30m")
            ->assertOk()->assertJsonCount(1, 'data');

        $this->withClubToken($token)->deleteJson("/api/physical-evaluations/{$id}")->assertNoContent();
        $this->withClubToken($token)->getJson("/api/physical-evaluations/{$id}")->assertNotFound();
        $this->inTenant($this->tenantA(), fn () => $this->assertSoftDeleted('physical_evaluations', ['id' => $id], 'tenant'));
    }

    public function test_a_physical_trainer_evaluates_only_own_categories(): void
    {
        $c = $this->club();
        $admin = $this->clubToken($this->tenantA(), roles: ['admin']);
        $other = $this->withClubToken($admin)
            ->postJson('/api/physical-evaluations', $this->evaluation($c['carla']->id, ['evaluator_coach_id' => $c['marta']->id]))
            ->assertCreated()->json('data.id');

        $token = $this->coachToken($c['marta'], 'physical_trainer');

        // Sin indicar evaluador: es el profe que la carga.
        $this->withClubToken($token)->postJson('/api/physical-evaluations', $this->evaluation($c['ana']->id))
            ->assertCreated()->assertJsonPath('data.evaluator_coach_id', $c['marta']->id);
        $this->withClubToken($token)->postJson('/api/physical-evaluations', $this->evaluation($c['carla']->id))->assertNotFound();

        $this->withClubToken($token)->getJson('/api/physical-evaluations')->assertOk()->assertJsonCount(1, 'data');
        $this->withClubToken($token)->getJson("/api/physical-evaluations/{$other}")->assertNotFound();
        $this->withClubToken($token)->deleteJson("/api/physical-evaluations/{$other}")->assertNotFound();
    }

    public function test_season_objectives(): void
    {
        $c = $this->club();
        $admin = $this->clubToken($this->tenantA(), roles: ['admin']);

        $id = $this->withClubToken($admin)->postJson('/api/season-objectives', [
            'season_id' => $c['season']->id,
            'category_id' => $c['u15']->id,
            'title' => 'Llegar a semifinales',
            'target_value' => 8,
            'current_value' => 2,
            'unit' => 'partidos ganados',
        ])->assertCreated()->assertJsonPath('data.progress', 25)->assertJsonPath('data.status', 'in_progress')->json('data.id');

        $this->withClubToken($admin)->postJson('/api/season-objectives', [
            'season_id' => $c['season']->id, 'title' => 'Sumar 20 jugadores nuevos',
        ])->assertCreated()->assertJsonPath('data.progress', null);

        $this->withClubToken($admin)->patchJson("/api/season-objectives/{$id}", ['current_value' => 10, 'status' => 'achieved'])
            ->assertOk()->assertJsonPath('data.progress', 100)->assertJsonPath('data.status', 'achieved');

        // Por defecto, los de la temporada activa; el del club va primero.
        $this->withClubToken($admin)->getJson('/api/season-objectives')
            ->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.category_id', null);

        $coach = $this->coachToken($c['marta']);
        $this->withClubToken($coach)->getJson('/api/season-objectives')->assertOk()->assertJsonCount(2, 'data');
        $this->withClubToken($coach)->patchJson("/api/season-objectives/{$id}", ['current_value' => 3])->assertForbidden();

        $this->withClubToken($admin)->deleteJson("/api/season-objectives/{$id}")->assertNoContent();
    }

    public function test_standings_are_loaded_by_hand_per_category(): void
    {
        $c = $this->club();
        $admin = $this->clubToken($this->tenantA(), roles: ['admin']);
        $competition = $this->inTenant($this->tenantA(), function () use ($c) {
            $competition = Competition::factory()->create(['sport_id' => $c['u15']->sport_id, 'season_id' => $c['season']->id]);
            $competition->categories()->attach($c['u15']->id);

            return $competition;
        });

        $rows = [
            ['team_name' => 'Club Rival', 'played' => 5, 'won' => 4, 'lost' => 1, 'points' => 9],
            ['team_name' => 'Nosotros', 'is_own' => true, 'played' => 5, 'won' => 3, 'lost' => 2, 'points' => 8],
        ];

        $this->withClubToken($admin)->putJson("/api/competitions/{$competition->id}/standings", ['category_id' => $c['u17']->id, 'rows' => $rows])
            ->assertUnprocessable()->assertJsonValidationErrors('category_id');
        $this->withClubToken($admin)->putJson("/api/competitions/{$competition->id}/standings", ['category_id' => $c['u15']->id, 'rows' => [...$rows, ['team_name' => 'club rival']]])
            ->assertUnprocessable()->assertJsonValidationErrors('rows.0.team_name');

        $this->withClubToken($admin)->putJson("/api/competitions/{$competition->id}/standings", ['category_id' => $c['u15']->id, 'rows' => $rows])
            ->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('data.1.position', 2)->assertJsonPath('data.1.is_own', true)->assertJsonPath('data.1.points', 8);

        // Guardar de nuevo reemplaza la tabla.
        $this->withClubToken($admin)->putJson("/api/competitions/{$competition->id}/standings", ['category_id' => $c['u15']->id, 'rows' => array_reverse($rows)])->assertOk();
        $this->withClubToken($admin)->getJson("/api/competitions/{$competition->id}/standings?category_id={$c['u15']->id}")
            ->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.team_name', 'Nosotros');

        $coach = $this->coachToken($c['marta']);
        $this->withClubToken($coach)->getJson("/api/competitions/{$competition->id}/standings")->assertOk();
        $this->withClubToken($coach)->putJson("/api/competitions/{$competition->id}/standings", ['category_id' => $c['u15']->id, 'rows' => []])->assertForbidden();
    }

    public function test_analytics_by_category_and_player(): void
    {
        $c = $this->club();
        $admin = $this->clubToken($this->tenantA(), roles: ['admin']);

        $this->inTenant($this->tenantA(), function () use ($c) {
            $recorder = ClubUser::query()->value('id');
            $mark = fn (Player $p, array $session, string $status) => DB::connection('tenant')->table('player_attendance')
                ->insert(['player_id' => $p->id, ...$session, 'status' => $status, 'recorded_by' => $recorder]);

            foreach ([10, 5] as $daysAgo) {
                $t = Training::factory()->create(['category_id' => $c['u15']->id, 'starts_at' => now()->subDays($daysAgo)->setTime(18, 0), 'ends_at' => now()->subDays($daysAgo)->setTime(19, 30)]);
                $mark($c['ana'], ['training_id' => $t->id], 'present');
                $mark($c['beto'], ['training_id' => $t->id], 'absent');
            }

            $won = GameMatch::factory()->create(['category_id' => $c['u15']->id, 'match_date' => now()->subDays(8), 'status' => 'played', 'our_score' => 70, 'opponent_score' => 60]);
            GameMatch::factory()->create(['category_id' => $c['u15']->id, 'match_date' => now()->subDays(2), 'status' => 'played', 'our_score' => 50, 'opponent_score' => 55]);
            // Fuera del período: no cuenta.
            GameMatch::factory()->create(['category_id' => $c['u15']->id, 'match_date' => now()->subDays(200), 'status' => 'played', 'our_score' => 90, 'opponent_score' => 10]);
            $mark($c['ana'], ['match_id' => $won->id], 'late');
            $mark($c['beto'], ['match_id' => $won->id], 'excused');
        });

        $this->withClubToken($admin)->postJson('/api/physical-evaluations', $this->evaluation($c['ana']->id, ['evaluator_coach_id' => $c['marta']->id]))->assertCreated();

        $u15 = collect($this->withClubToken($admin)->getJson('/api/analytics/categories')->assertOk()->json('data'))
            ->firstWhere('category_id', $c['u15']->id);
        $this->assertSame(2, $u15['players']);
        $this->assertSame(3, $u15['sessions']);
        $this->assertSame(['recorded' => 6, 'present' => 3, 'rate' => 50], $u15['attendance']);
        $this->assertSame(['played' => 2, 'won' => 1, 'drawn' => 0, 'lost' => 1, 'goals_for' => 120, 'goals_against' => 115, 'form' => ['W', 'L']], $u15['results']);
        $this->assertSame(1, $u15['evaluations']);

        $detail = $this->withClubToken($admin)->getJson("/api/analytics/categories/{$c['u15']->id}")->assertOk()->json('data');
        $this->assertSame(['present' => 2, 'late' => 1, 'excused' => 1, 'absent' => 2], $detail['by_status']);
        $this->assertSame(6, array_sum(array_column($detail['weekly'], 'recorded')));
        $this->assertGreaterThanOrEqual(13, count($detail['weekly']));
        $this->assertSame(['W', 'L'], array_column($detail['matches'], 'result'));
        $this->assertSame(70, $detail['matches'][0]['our_score']);
        $this->assertSame(['recorded' => 6, 'present' => 3, 'rate' => 50], $detail['attendance']);

        $players = $this->withClubToken($admin)->getJson("/api/analytics/categories/{$c['u15']->id}/players")->assertOk()->json('data');
        $this->assertSame('Ana Test', $players[0]['player_name']);
        $this->assertSame(100, $players[0]['attendance']['rate']);
        $this->assertSame(1, $players[0]['evaluations']);
        $this->assertSame(0, $players[1]['attendance']['rate']);
        $this->assertSame(['present' => 0, 'late' => 0, 'excused' => 1, 'absent' => 2], $players[1]['by_status']);
        // Racha desde la última sesión: faltó sin aviso al entrenamiento de hace 5 días y antes avisó.
        $this->assertSame(1, $players[1]['absent_streak']);

        $coach = $this->coachToken($c['marta']);
        $this->withClubToken($coach)->getJson('/api/analytics/categories')->assertOk()->assertJsonCount(1, 'data');
        $this->withClubToken($coach)->getJson("/api/analytics/categories/{$c['u17']->id}/players")->assertNotFound();
        $this->withClubToken($coach)->getJson("/api/analytics/categories/{$c['u17']->id}")->assertNotFound();
        // El profe ve las evaluaciones pero no las carga.
        $this->withClubToken($coach)->postJson('/api/physical-evaluations', $this->evaluation($c['ana']->id))->assertForbidden();
    }

    public function test_permissions_and_isolation(): void
    {
        $this->getJson('/api/physical-evaluations')->assertUnauthorized();

        $c = $this->club();
        $admin = $this->clubToken($this->tenantA(), roles: ['admin']);
        $id = $this->withClubToken($admin)
            ->postJson('/api/physical-evaluations', $this->evaluation($c['ana']->id, ['evaluator_coach_id' => $c['marta']->id]))
            ->assertCreated()->json('data.id');

        $this->withClubToken($this->clubToken($this->tenantA(), roles: ['maintenance_manager']))->getJson('/api/physical-evaluations')->assertForbidden();
        $this->withClubToken($this->clubToken($this->tenantB(), roles: ['admin']))->getJson("/api/physical-evaluations/{$id}")->assertNotFound();
    }
}
