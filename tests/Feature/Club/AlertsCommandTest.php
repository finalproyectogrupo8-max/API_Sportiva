<?php

namespace Tests\Feature\Club;

use App\Models\Club\AlertRecord;
use App\Models\Club\Category;
use App\Models\Club\ClubUser;
use App\Models\Club\Coach;
use App\Models\Club\Competition;
use App\Models\Club\CompetitionRequirement;
use App\Models\Club\GameMatch;
use App\Models\Club\Notification;
use App\Models\Club\Player;
use App\Models\Club\PlayerAttendance;
use App\Models\Club\PlayerCategoryMembership;
use App\Models\Club\PlayerRequirement;
use App\Models\Club\Season;
use App\Models\Club\Sport;
use App\Models\Club\Training;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class AlertsCommandTest extends TestCase
{
    use InteractsWithTenants;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_rule_1_consecutive_unjustified_absences_alerts_coach_and_coordinator_without_duplicates(): void
    {
        Carbon::setTestNow('2027-04-10 10:00:00');

        $coachUser = User::factory()->create();
        $coordUser = User::factory()->create();
        $otherCoachUser = User::factory()->create();

        $this->clubToken($this->tenantA(), $coachUser, ['coach']);
        $this->clubToken($this->tenantA(), $coordUser, ['coordinator']);
        $this->clubToken($this->tenantA(), $otherCoachUser, ['coach']);

        $player = $this->inTenant($this->tenantA(), function () use ($coachUser, $otherCoachUser) {
            $sport = Sport::factory()->create(['name' => 'Fútbol']);
            $season = Season::factory()->create(['name' => '2027']);

            $catA = Category::factory()->create(['name' => 'Sub-15 A', 'sport_id' => $sport->id, 'season_id' => $season->id]);
            $catB = Category::factory()->create(['name' => 'Sub-15 B', 'sport_id' => $sport->id, 'season_id' => $season->id]);

            $clubCoach = ClubUser::query()->where('global_user_id', $coachUser->id)->sole();
            $coach = Coach::factory()->create(['club_user_id' => $clubCoach->id]);
            DB::connection('tenant')->table('category_coaches')->insert([
                'category_id' => $catA->id,
                'coach_id' => $coach->id,
                'assignment_type' => 'head_coach',
            ]);

            $clubOtherCoach = ClubUser::query()->where('global_user_id', $otherCoachUser->id)->sole();
            $otherCoach = Coach::factory()->create(['club_user_id' => $clubOtherCoach->id]);
            DB::connection('tenant')->table('category_coaches')->insert([
                'category_id' => $catB->id,
                'coach_id' => $otherCoach->id,
                'assignment_type' => 'head_coach',
            ]);

            $player = Player::factory()->create(['first_name' => 'Juan', 'last_name' => 'Pérez']);
            PlayerCategoryMembership::create([
                'player_id' => $player->id,
                'category_id' => $catA->id,
                'start_date' => '2027-01-01',
                'status' => 'active',
            ]);

            // 3 consecutive trainings with unjustified absence (absent)
            for ($i = 1; $i <= 3; $i++) {
                $training = Training::factory()->create([
                    'category_id' => $catA->id,
                    'starts_at' => "2027-04-0{$i} 18:00:00",
                    'ends_at' => "2027-04-0{$i} 20:00:00",
                ]);

                PlayerAttendance::query()->create([
                    'player_id' => $player->id,
                    'training_id' => $training->id,
                    'status' => 'absent',
                    'recorded_by' => $clubCoach->id,
                ]);
            }

            return $player;
        });

        // First run: generates alerts
        $this->artisan('alerts:run', ['--club' => $this->tenantA()->slug])
            ->assertSuccessful();

        $notifications = $this->inTenant($this->tenantA(), fn () => Notification::query()->get());
        $this->assertCount(2, $notifications); // Coach A and Coordinator

        $this->inTenant($this->tenantA(), function () use ($coachUser, $coordUser, $otherCoachUser) {
            $coachClubUser = ClubUser::query()->where('global_user_id', $coachUser->id)->sole();
            $coordClubUser = ClubUser::query()->where('global_user_id', $coordUser->id)->sole();
            $otherCoachClubUser = ClubUser::query()->where('global_user_id', $otherCoachUser->id)->sole();

            $this->assertTrue(Notification::query()->where('club_user_id', $coachClubUser->id)->where('type', 'attendance_drop')->exists());
            $this->assertTrue(Notification::query()->where('club_user_id', $coordClubUser->id)->where('type', 'attendance_drop')->exists());
            $this->assertFalse(Notification::query()->where('club_user_id', $otherCoachClubUser->id)->exists());
        });

        // Second run: no duplicate notification is created
        $this->artisan('alerts:run', ['--club' => $this->tenantA()->slug])
            ->assertSuccessful();

        $this->assertSame(2, $this->inTenant($this->tenantA(), fn () => Notification::query()->count()));

        // When condition resolves (player attends next event), alert record is marked resolved
        $this->inTenant($this->tenantA(), function () use ($player, $coachUser) {
            $clubCoach = ClubUser::query()->where('global_user_id', $coachUser->id)->sole();
            $cat = Category::query()->first();

            $training = Training::factory()->create([
                'category_id' => $cat->id,
                'starts_at' => '2027-04-09 18:00:00',
                'ends_at' => '2027-04-09 20:00:00',
            ]);

            PlayerAttendance::query()->create([
                'player_id' => $player->id,
                'training_id' => $training->id,
                'status' => 'present',
                'recorded_by' => $clubCoach->id,
            ]);
        });

        $this->artisan('alerts:run', ['--club' => $this->tenantA()->slug])->assertSuccessful();

        $resolvedAlerts = $this->inTenant($this->tenantA(), fn () => AlertRecord::query()->whereNotNull('resolved_at')->count());
        $this->assertSame(1, $resolvedAlerts);
    }

    public function test_rule_1_monthly_attendance_drop_below_50_percent_when_prior_was_above_75_percent(): void
    {
        Carbon::setTestNow('2027-04-15 10:00:00');

        $coachUser = User::factory()->create();
        $this->clubToken($this->tenantA(), $coachUser, ['coach']);

        $this->inTenant($this->tenantA(), function () use ($coachUser) {
            $sport = Sport::factory()->create();
            $season = Season::factory()->create();
            $cat = Category::factory()->create(['sport_id' => $sport->id, 'season_id' => $season->id]);

            $clubCoach = ClubUser::query()->where('global_user_id', $coachUser->id)->sole();
            $coach = Coach::factory()->create(['club_user_id' => $clubCoach->id]);
            DB::connection('tenant')->table('category_coaches')->insert([
                'category_id' => $cat->id,
                'coach_id' => $coach->id,
                'assignment_type' => 'head_coach',
            ]);

            $player = Player::factory()->create();
            PlayerCategoryMembership::create([
                'player_id' => $player->id,
                'category_id' => $cat->id,
                'start_date' => '2027-01-01',
                'status' => 'active',
            ]);

            // Prior to 30 days ago (prior to 2027-03-16): 4 events, all 4 present (100% > 75%)
            for ($i = 1; $i <= 4; $i++) {
                $tr = Training::factory()->create([
                    'category_id' => $cat->id,
                    'starts_at' => "2027-02-0{$i} 18:00:00",
                    'ends_at' => "2027-02-0{$i} 20:00:00",
                ]);
                PlayerAttendance::query()->create([
                    'player_id' => $player->id,
                    'training_id' => $tr->id,
                    'status' => 'present',
                    'recorded_by' => $clubCoach->id,
                ]);
            }

            // In the last 30 days (since 2027-03-16): 4 events, 1 present, 1 late, 2 absent (50% attended? Let's make it 1 present, 3 absent = 25% < 50%)
            // Neither is 3 consecutive absences if arranged as: absent, present, absent, absent (2 consecutive)
            $statuses = ['absent', 'present', 'absent', 'absent'];
            foreach ($statuses as $index => $st) {
                $day = 20 + $index;
                $tr = Training::factory()->create([
                    'category_id' => $cat->id,
                    'starts_at' => "2027-03-{$day} 18:00:00",
                    'ends_at' => "2027-03-{$day} 20:00:00",
                ]);
                PlayerAttendance::query()->create([
                    'player_id' => $player->id,
                    'training_id' => $tr->id,
                    'status' => $st,
                    'recorded_by' => $clubCoach->id,
                ]);
            }
        });

        $this->artisan('alerts:run', ['--club' => $this->tenantA()->slug])->assertSuccessful();

        $notifications = $this->inTenant($this->tenantA(), fn () => Notification::query()->where('type', 'attendance_drop')->get());
        $this->assertNotEmpty($notifications);
        $this->assertStringContainsString('último mes', $notifications->first()->body);

        // Deduplication
        $this->artisan('alerts:run', ['--club' => $this->tenantA()->slug])->assertSuccessful();
        $this->assertCount($notifications->count(), $this->inTenant($this->tenantA(), fn () => Notification::query()->get()));
    }

    public function test_rule_2_requirement_expiring_in_30_days_alerts_once(): void
    {
        Carbon::setTestNow('2027-04-01 10:00:00');

        $coachUser = User::factory()->create();
        $coordUser = User::factory()->create();

        $this->clubToken($this->tenantA(), $coachUser, ['coach']);
        $this->clubToken($this->tenantA(), $coordUser, ['coordinator']);

        $this->inTenant($this->tenantA(), function () use ($coachUser) {
            $sport = Sport::factory()->create();
            $season = Season::factory()->create();
            $cat = Category::factory()->create(['sport_id' => $sport->id, 'season_id' => $season->id]);

            $clubCoach = ClubUser::query()->where('global_user_id', $coachUser->id)->sole();
            $coach = Coach::factory()->create(['club_user_id' => $clubCoach->id]);
            DB::connection('tenant')->table('category_coaches')->insert([
                'category_id' => $cat->id,
                'coach_id' => $coach->id,
                'assignment_type' => 'head_coach',
            ]);

            $player = Player::factory()->create(['first_name' => 'Mateo', 'last_name' => 'González']);
            PlayerCategoryMembership::create([
                'player_id' => $player->id,
                'category_id' => $cat->id,
                'start_date' => '2027-01-01',
                'status' => 'active',
            ]);

            $competition = Competition::factory()->create(['sport_id' => $sport->id, 'season_id' => $season->id]);
            $compReq = CompetitionRequirement::create([
                'competition_id' => $competition->id,
                'name' => 'Apto Médico',
                'is_required' => true,
                'requires_file' => true,
                'sort' => 1,
            ]);

            // Requirement expiring in 20 days (within 30 days)
            PlayerRequirement::create([
                'player_id' => $player->id,
                'competition_requirement_id' => $compReq->id,
                'status' => PlayerRequirement::APPROVED,
                'expires_at' => '2027-04-21',
            ]);

            // Another requirement expiring in 90 days (not alerted)
            $otherPlayer = Player::factory()->create();
            PlayerCategoryMembership::create([
                'player_id' => $otherPlayer->id,
                'category_id' => $cat->id,
                'start_date' => '2027-01-01',
                'status' => 'active',
            ]);
            PlayerRequirement::create([
                'player_id' => $otherPlayer->id,
                'competition_requirement_id' => $compReq->id,
                'status' => PlayerRequirement::APPROVED,
                'expires_at' => '2027-07-01',
            ]);
        });

        // First run
        $this->artisan('alerts:run', ['--club' => $this->tenantA()->slug])->assertSuccessful();

        $notifications = $this->inTenant($this->tenantA(), fn () => Notification::query()->where('type', 'requirement_expiring')->get());
        $this->assertCount(2, $notifications); // Coach and coordinator
        $this->assertStringContainsString('Apto Médico', $notifications->first()->title);
        $this->assertStringContainsString('Mateo González', $notifications->first()->body);

        // Second run: no duplication
        $this->artisan('alerts:run', ['--club' => $this->tenantA()->slug])->assertSuccessful();
        $this->assertSame(2, $this->inTenant($this->tenantA(), fn () => Notification::query()->count()));
    }

    public function test_rule_3_registration_closing_in_7_days_with_ineligible_players_alerts_once(): void
    {
        Carbon::setTestNow('2027-04-01 10:00:00');

        $coachUser = User::factory()->create();
        $coordUser = User::factory()->create();

        $this->clubToken($this->tenantA(), $coachUser, ['coach']);
        $this->clubToken($this->tenantA(), $coordUser, ['coordinator']);

        $this->inTenant($this->tenantA(), function () use ($coachUser) {
            $sport = Sport::factory()->create();
            $season = Season::factory()->create();
            $cat = Category::factory()->create(['name' => 'Sub-17', 'sport_id' => $sport->id, 'season_id' => $season->id]);

            $clubCoach = ClubUser::query()->where('global_user_id', $coachUser->id)->sole();
            $coach = Coach::factory()->create(['club_user_id' => $clubCoach->id]);
            DB::connection('tenant')->table('category_coaches')->insert([
                'category_id' => $cat->id,
                'coach_id' => $coach->id,
                'assignment_type' => 'head_coach',
            ]);

            $player = Player::factory()->create(['first_name' => 'Lucas', 'last_name' => 'Romero']);
            PlayerCategoryMembership::create([
                'player_id' => $player->id,
                'category_id' => $cat->id,
                'start_date' => '2027-01-01',
                'status' => 'active',
            ]);

            // Competition closing in 5 days (within 7 days)
            $competition = Competition::factory()->create([
                'name' => 'Torneo Apertura 2027',
                'sport_id' => $sport->id,
                'season_id' => $season->id,
                'registration_deadline' => '2027-04-06',
            ]);
            $competition->categories()->attach($cat->id);

            // Mandatory requirement that is still pending (player is ineligible)
            CompetitionRequirement::create([
                'competition_id' => $competition->id,
                'name' => 'Ficha médica',
                'is_required' => true,
                'requires_file' => false,
                'sort' => 1,
            ]);
        });

        // Run
        $this->artisan('alerts:run', ['--club' => $this->tenantA()->slug])->assertSuccessful();

        $notifications = $this->inTenant($this->tenantA(), fn () => Notification::query()->where('type', 'registration_closing')->get());
        $this->assertCount(2, $notifications);
        $this->assertStringContainsString('Torneo Apertura 2027', $notifications->first()->title);
        $this->assertStringContainsString('sin habilitar', $notifications->first()->body);

        // Deduplication
        $this->artisan('alerts:run', ['--club' => $this->tenantA()->slug])->assertSuccessful();
        $this->assertSame(2, $this->inTenant($this->tenantA(), fn () => Notification::query()->count()));
    }

    public function test_rule_4_callup_pending_within_48_hours_of_match_alerts_once(): void
    {
        Carbon::setTestNow('2027-04-01 10:00:00');

        $coachUser = User::factory()->create();
        $coordUser = User::factory()->create();

        $this->clubToken($this->tenantA(), $coachUser, ['coach']);
        $this->clubToken($this->tenantA(), $coordUser, ['coordinator']);

        $match = $this->inTenant($this->tenantA(), function () use ($coachUser) {
            $sport = Sport::factory()->create();
            $season = Season::factory()->create();
            $cat = Category::factory()->create(['name' => 'Primera', 'sport_id' => $sport->id, 'season_id' => $season->id]);

            $clubCoach = ClubUser::query()->where('global_user_id', $coachUser->id)->sole();
            $coach = Coach::factory()->create(['club_user_id' => $clubCoach->id]);
            DB::connection('tenant')->table('category_coaches')->insert([
                'category_id' => $cat->id,
                'coach_id' => $coach->id,
                'assignment_type' => 'head_coach',
            ]);

            // Match in 30 hours (within 48 hours), callup not published
            return GameMatch::factory()->create([
                'category_id' => $cat->id,
                'opponent' => 'Rival FC',
                'match_date' => '2027-04-02 16:00:00',
                'callup_published_at' => null,
                'status' => 'scheduled',
            ]);
        });

        $this->artisan('alerts:run', ['--club' => $this->tenantA()->slug])->assertSuccessful();

        $notifications = $this->inTenant($this->tenantA(), fn () => Notification::query()->where('type', 'callup_pending')->get());
        $this->assertCount(2, $notifications);
        $this->assertStringContainsString('Rival FC', $notifications->first()->title);

        // Deduplication
        $this->artisan('alerts:run', ['--club' => $this->tenantA()->slug])->assertSuccessful();
        $this->assertSame(2, $this->inTenant($this->tenantA(), fn () => Notification::query()->count()));

        // When callup is published, resolving works
        $this->inTenant($this->tenantA(), function () use ($match) {
            $match->update(['callup_published_at' => now()]);
        });

        $this->artisan('alerts:run', ['--club' => $this->tenantA()->slug])->assertSuccessful();
        $resolvedCount = $this->inTenant($this->tenantA(), fn () => AlertRecord::query()->where('alert_type', 'callup_pending')->whereNotNull('resolved_at')->count());
        $this->assertSame(1, $resolvedCount);
    }

    public function test_recipients_scope_isolation_profe_and_sport_coordinator(): void
    {
        Carbon::setTestNow('2027-04-01 10:00:00');

        $futbolCoachUser = User::factory()->create();
        $basquetCoachUser = User::factory()->create();
        $futbolCoordUser = User::factory()->create();
        $generalCoordUser = User::factory()->create();

        $this->clubToken($this->tenantA(), $futbolCoachUser, ['coach']);
        $this->clubToken($this->tenantA(), $basquetCoachUser, ['coach']);
        $this->clubToken($this->tenantA(), $futbolCoordUser, ['sport_coordinator']);
        $this->clubToken($this->tenantA(), $generalCoordUser, ['coordinator']);

        $this->inTenant($this->tenantA(), function () use ($futbolCoachUser, $basquetCoachUser, $futbolCoordUser) {
            $futbol = Sport::factory()->create(['name' => 'Fútbol']);
            $basquet = Sport::factory()->create(['name' => 'Básquet']);
            $season = Season::factory()->create();

            $futbolCat = Category::factory()->create(['name' => 'Fútbol U15', 'sport_id' => $futbol->id, 'season_id' => $season->id]);
            $basquetCat = Category::factory()->create(['name' => 'Básquet U15', 'sport_id' => $basquet->id, 'season_id' => $season->id]);

            // Assign sports to futbol sport coordinator
            $clubFutbolCoord = ClubUser::query()->where('global_user_id', $futbolCoordUser->id)->sole();
            $clubFutbolCoord->sports()->attach($futbol->id);

            // Coaches assignments
            $clubFutbolCoach = ClubUser::query()->where('global_user_id', $futbolCoachUser->id)->sole();
            $futbolCoach = Coach::factory()->create(['club_user_id' => $clubFutbolCoach->id]);
            DB::connection('tenant')->table('category_coaches')->insert(['category_id' => $futbolCat->id, 'coach_id' => $futbolCoach->id, 'assignment_type' => 'head_coach']);

            $clubBasquetCoach = ClubUser::query()->where('global_user_id', $basquetCoachUser->id)->sole();
            $basquetCoach = Coach::factory()->create(['club_user_id' => $clubBasquetCoach->id]);
            DB::connection('tenant')->table('category_coaches')->insert(['category_id' => $basquetCat->id, 'coach_id' => $basquetCoach->id, 'assignment_type' => 'head_coach']);

            // Football match pending callup
            GameMatch::factory()->create([
                'category_id' => $futbolCat->id,
                'opponent' => 'Defensa',
                'match_date' => '2027-04-02 15:00:00',
                'callup_published_at' => null,
                'status' => 'scheduled',
            ]);
        });

        $this->artisan('alerts:run', ['--club' => $this->tenantA()->slug])->assertSuccessful();

        $this->inTenant($this->tenantA(), function () use ($futbolCoachUser, $basquetCoachUser, $futbolCoordUser, $generalCoordUser) {
            $futbolCoachId = ClubUser::query()->where('global_user_id', $futbolCoachUser->id)->sole()->id;
            $basquetCoachId = ClubUser::query()->where('global_user_id', $basquetCoachUser->id)->sole()->id;
            $futbolCoordId = ClubUser::query()->where('global_user_id', $futbolCoordUser->id)->sole()->id;
            $generalCoordId = ClubUser::query()->where('global_user_id', $generalCoordUser->id)->sole()->id;

            // Futbol coach received it
            $this->assertTrue(Notification::query()->where('club_user_id', $futbolCoachId)->exists());
            // Futbol sport coordinator received it
            $this->assertTrue(Notification::query()->where('club_user_id', $futbolCoordId)->exists());
            // General coordinator received it
            $this->assertTrue(Notification::query()->where('club_user_id', $generalCoordId)->exists());
            // Basketball coach DID NOT receive it
            $this->assertFalse(Notification::query()->where('club_user_id', $basquetCoachId)->exists());
        });
    }

    public function test_club_isolation_alerts_in_tenant_a_do_not_notify_tenant_b(): void
    {
        Carbon::setTestNow('2027-04-01 10:00:00');

        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->clubToken($this->tenantA(), $userA, ['coordinator']);
        $this->clubToken($this->tenantB(), $userB, ['coordinator']);

        $this->inTenant($this->tenantA(), function () {
            $sport = Sport::factory()->create();
            $season = Season::factory()->create();
            $cat = Category::factory()->create(['sport_id' => $sport->id, 'season_id' => $season->id]);

            GameMatch::factory()->create([
                'category_id' => $cat->id,
                'match_date' => '2027-04-02 15:00:00',
                'callup_published_at' => null,
                'status' => 'scheduled',
            ]);
        });

        $this->artisan('alerts:run')->assertSuccessful();

        $this->assertGreaterThan(0, $this->inTenant($this->tenantA(), fn () => Notification::query()->count()));
        $this->assertSame(0, $this->inTenant($this->tenantB(), fn () => Notification::query()->count()));
    }

    public function test_dry_run_does_not_persist_notifications_or_records(): void
    {
        Carbon::setTestNow('2027-04-01 10:00:00');

        $user = User::factory()->create();
        $this->clubToken($this->tenantA(), $user, ['coordinator']);

        $this->inTenant($this->tenantA(), function () {
            $sport = Sport::factory()->create();
            $season = Season::factory()->create();
            $cat = Category::factory()->create(['sport_id' => $sport->id, 'season_id' => $season->id]);

            GameMatch::factory()->create([
                'category_id' => $cat->id,
                'match_date' => '2027-04-02 15:00:00',
                'callup_published_at' => null,
                'status' => 'scheduled',
            ]);
        });

        $this->artisan('alerts:run', ['--club' => $this->tenantA()->slug, '--dry-run' => true])
            ->assertSuccessful();

        $this->assertSame(0, $this->inTenant($this->tenantA(), fn () => Notification::query()->count()));
        $this->assertSame(0, $this->inTenant($this->tenantA(), fn () => AlertRecord::query()->count()));
    }

    public function test_failing_club_does_not_halt_execution_of_other_clubs(): void
    {
        Carbon::setTestNow('2027-04-01 10:00:00');

        $userB = User::factory()->create();
        $this->clubToken($this->tenantB(), $userB, ['coordinator']);

        $this->inTenant($this->tenantB(), function () {
            $sport = Sport::factory()->create();
            $season = Season::factory()->create();
            $cat = Category::factory()->create(['sport_id' => $sport->id, 'season_id' => $season->id]);

            GameMatch::factory()->create([
                'category_id' => $cat->id,
                'match_date' => '2027-04-02 15:00:00',
                'callup_published_at' => null,
                'status' => 'scheduled',
            ]);
        });

        // Set an invalid database_name on tenantA to trigger an exception
        $this->tenantA()->update(['database_name' => 'invalid_db_name_causes_error']);

        // Running alerts:run should fail because tenantA failed, but tenantB was evaluated!
        $this->artisan('alerts:run')->assertFailed();

        // Tenant B still received its notifications!
        $this->assertGreaterThan(0, $this->inTenant($this->tenantB(), fn () => Notification::query()->count()));
    }
}
