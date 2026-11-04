<?php

namespace Database\Seeders;

use App\Club\Access\ClubMembers;
use App\Club\Access\SyncClubPermissions;
use App\Enums\TenantStatus;
use App\Models\Club\AlertRecord;
use App\Models\Club\Category;
use App\Models\Club\Coach;
use App\Models\Club\GameMatch;
use App\Models\Club\Notification;
use App\Models\Club\Player;
use App\Models\Club\PlayerAttendance;
use App\Models\Club\PlayerCategoryMembership;
use App\Models\Club\Position;
use App\Models\Club\Season;
use App\Models\Club\Sport;
use App\Models\Club\Training;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Tenancy\TenantDatabase;
use Illuminate\Database\Seeder;
use RuntimeException;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException('Los datos de demostración solo se pueden cargar en el entorno local.');
        }

        $admin = $this->seedUser('demo.admin@example.test', 'Demo Admin');
        $coach = $this->seedUser('demo.coach@example.test', 'Demo Coach');
        $alternateAdmin = $this->seedUser('demo.alternate@example.test', 'Demo Alternate Admin');
        $invitedUser = $this->seedUser('demo.invited@example.test', 'Demo Invited User', invited: true);

        $this->seedTenant(
            slug: 'demo_activo',
            name: 'Club Demo Activo',
            city: 'Rosario',
            admin: $admin,
            coach: $coach,
            scenario: [
                'sport' => 'Fútbol',
                'category' => 'Sub-15 Masculino',
                'branch' => 'male',
                'positions' => ['Arquero', 'Defensor', 'Delantero'],
                'players' => [
                    ['first_name' => 'Luca', 'last_name' => 'Gomez', 'birth_date' => '2012-04-12', 'gender' => 'male', 'status' => 'active', 'position' => 'Delantero', 'attendance' => 'present'],
                    ['first_name' => 'Mateo', 'last_name' => 'Lopez', 'birth_date' => '2011-09-03', 'gender' => 'male', 'status' => 'active', 'position' => 'Defensor', 'attendance' => 'late'],
                    ['first_name' => 'Tomas', 'last_name' => 'Diaz', 'birth_date' => '2012-01-21', 'gender' => 'male', 'status' => 'active', 'position' => 'Arquero', 'attendance' => 'absent'],
                ],
                'opponent' => 'Club del Parque',
                'match_status' => 'scheduled',
                'match_date' => '2026-11-15 15:00:00',
                'alert_resolved' => false,
            ],
        );

        $this->seedTenant(
            slug: 'demo_alternativo',
            name: 'Club Demo Alternativo',
            city: 'Cordoba',
            admin: $alternateAdmin,
            coach: null,
            invitedUser: $invitedUser,
            scenario: [
                'sport' => 'Hockey',
                'category' => 'Sub-16 Mixto',
                'branch' => 'mixed',
                'positions' => ['Arquero', 'Defensor', 'Delantero'],
                'players' => [
                    ['first_name' => 'Mora', 'last_name' => 'Perez', 'birth_date' => '2011-06-14', 'gender' => 'female', 'status' => 'active', 'position' => 'Delantero', 'attendance' => 'excused'],
                    ['first_name' => 'Bruno', 'last_name' => 'Sosa', 'birth_date' => '2010-11-02', 'gender' => 'male', 'status' => 'inactive', 'position' => 'Defensor', 'attendance' => 'absent'],
                ],
                'opponent' => 'Club Horizonte',
                'match_status' => 'postponed',
                'match_date' => '2026-11-20 16:00:00',
                'alert_resolved' => true,
            ],
        );
    }

    private function seedUser(string $email, string $name, bool $invited = false): User
    {
        $user = User::query()->firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => $name,
            'password' => 'sportiva-demo',
            'email_verified_at' => $invited ? null : now(),
            'is_platform_admin' => false,
            'is_active' => ! $invited,
            'invited_at' => $invited ? now() : null,
            'invite_accepted_at' => null,
        ])->save();

        return $user;
    }

    /**
     * @param  array{
     *     sport: string,
     *     category: string,
     *     branch: string,
     *     positions: list<string>,
     *     players: list<array{first_name: string, last_name: string, birth_date: string, gender: string, status: string, position: string, attendance: string}>,
     *     opponent: string,
     *     match_status: string,
     *     match_date: string,
     *     alert_resolved: bool
     * }  $scenario
     */
    private function seedTenant(
        string $slug,
        string $name,
        string $city,
        User $admin,
        ?User $coach,
        array $scenario,
        ?User $invitedUser = null,
    ): void {
        $tenant = Tenant::withTrashed()->firstOrNew(['slug' => $slug]);
        if ($tenant->trashed()) {
            $tenant->restore();
        }
        $tenant->forceFill([
            'name' => $name,
            'short_name' => $slug === 'demo_activo' ? 'Demo A' : 'Demo B',
            'city' => $city,
            'founded_year' => 1998,
            'primary_color' => $slug === 'demo_activo' ? '#1565C0' : '#2E7D32',
            'database_name' => 'club_'.$slug,
            'status' => TenantStatus::Provisioning->value,
        ])->save();

        TenantMembership::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'user_id' => $admin->id],
            ['is_active' => true],
        );

        if ($coach) {
            TenantMembership::query()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'user_id' => $coach->id],
                ['is_active' => true],
            );
        }

        if ($invitedUser) {
            TenantMembership::query()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'user_id' => $invitedUser->id],
                ['is_active' => false],
            );
        }

        $tenantDatabase = app(TenantDatabase::class);
        $tenantDatabase->create($tenant);
        $tenantDatabase->migrate($tenant);
        $tenantDatabase->run($tenant, function () use ($admin, $coach, $scenario): void {
            app(SyncClubPermissions::class)->sync();
            $members = app(ClubMembers::class);
            $sport = Sport::query()->firstOrCreate(
                ['name' => $scenario['sport']],
                ['is_active' => true],
            );

            $adminClubUser = $members->ensure($admin, ['admin'], sportIds: [$sport->id]);
            $coachClubUser = null;
            if ($coach) {
                $coachClubUser = $members->ensure($coach, ['coach'], sportIds: [$sport->id]);
                Coach::query()->updateOrCreate(
                    ['club_user_id' => $coachClubUser->id],
                    [
                        'first_name' => 'Demo',
                        'last_name' => 'Coach',
                        'email' => $coach->email,
                        'status' => 'active',
                    ],
                );
            }

            $season = Season::query()->firstOrCreate(
                ['name' => 'Temporada 2026'],
                ['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active'],
            );
            $category = Category::query()->firstOrCreate(
                ['sport_id' => $sport->id, 'season_id' => $season->id, 'name' => $scenario['category']],
                ['branch' => $scenario['branch'], 'min_age' => 13, 'max_age' => 16, 'is_active' => true],
            );
            $positions = [];
            foreach ($scenario['positions'] as $positionName) {
                $positions[$positionName] = Position::query()->firstOrCreate(
                    ['sport_id' => $sport->id, 'name' => $positionName],
                    ['is_active' => true],
                );
            }

            $players = [];
            foreach ($scenario['players'] as $playerData) {
                $player = Player::query()->firstOrCreate(
                    ['first_name' => $playerData['first_name'], 'last_name' => $playerData['last_name']],
                    [
                        'birth_date' => $playerData['birth_date'],
                        'gender' => $playerData['gender'],
                        'status' => $playerData['status'],
                    ],
                );
                $player->positions()->syncWithoutDetaching([$positions[$playerData['position']]->id]);

                PlayerCategoryMembership::query()->firstOrCreate(
                    ['player_id' => $player->id, 'category_id' => $category->id],
                    [
                        'start_date' => '2026-01-01',
                        'end_date' => $playerData['status'] === 'active' ? null : '2026-10-01',
                        'shirt_number' => count($players) + 1,
                        'status' => $playerData['status'] === 'active' ? 'active' : 'ended',
                    ],
                );
                $players[] = [$player, $playerData['attendance']];
            }

            $training = Training::query()->firstOrCreate(
                ['category_id' => $category->id, 'starts_at' => '2026-11-10 18:00:00'],
                [
                    'coach_id' => $coachClubUser ? Coach::query()->where('club_user_id', $coachClubUser->id)->value('id') : null,
                    'ends_at' => '2026-11-10 19:30:00',
                    'location' => 'Cancha principal',
                    'objectives' => 'Trabajo técnico y táctico',
                ],
            );
            foreach ($players as [$player, $attendance]) {
                PlayerAttendance::query()->firstOrCreate(
                    ['player_id' => $player->id, 'training_id' => $training->id],
                    ['status' => $attendance, 'recorded_by' => $adminClubUser->id],
                );
            }

            GameMatch::query()->firstOrCreate(
                ['category_id' => $category->id, 'opponent' => $scenario['opponent'], 'match_date' => $scenario['match_date']],
                [
                    'venue' => 'Cancha principal',
                    'is_home' => true,
                    'status' => $scenario['match_status'],
                    'our_score' => null,
                    'opponent_score' => null,
                ],
            );

            $absentPlayerEntry = collect($players)->first(fn (array $entry) => $entry[1] === 'absent');
            if (! $absentPlayerEntry) {
                throw new RuntimeException("El escenario demo de {$category->name} debe incluir una ausencia.");
            }
            $absentPlayer = $absentPlayerEntry[0];
            AlertRecord::query()->firstOrCreate(
                ['dedup_key' => 'demo:attendance-drop:'.$category->id],
                [
                    'alert_type' => 'attendance_drop',
                    'subject_type' => 'player',
                    'subject_id' => $absentPlayer->id,
                    'notified_at' => '2026-11-11 08:00:00',
                    'resolved_at' => $scenario['alert_resolved'] ? '2026-11-12 08:00:00' : null,
                ],
            );
            Notification::query()->firstOrCreate(
                ['club_user_id' => $adminClubUser->id, 'title' => 'Resumen de actividad demo'],
                [
                    'type' => 'general',
                    'body' => 'Hay novedades de asistencia y partidos para revisar.',
                    'data' => ['category_id' => $category->id],
                    'read_at' => $scenario['alert_resolved'] ? now() : null,
                ],
            );
        });

        $tenant->update(['status' => TenantStatus::Active->value]);
    }
}
