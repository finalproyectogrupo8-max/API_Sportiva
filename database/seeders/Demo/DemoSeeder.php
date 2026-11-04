<?php

namespace Database\Seeders\Demo;

use App\Club\Access\ClubMembers;
use App\Jobs\ProvisionTenant;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Tenancy\TenantDatabase;
use Database\Seeders\SupportTicketSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Datos de demo para levantar la app en local y mostrar todas las pantallas con información real.
 *
 * Crea la plataforma (usuarios, un club "demo" con su base club_demo, membresías y registros de
 * sesión/auditoría) y llena todas las tablas del club. Es idempotente: si el club demo ya existe,
 * no hace nada. Para regenerarlo, ver scripts/demo/regenerate-demo.sh.
 *
 * Todas las cuentas de club usan la contraseña DEMO_PASSWORD. Están listadas en docs/datos-demo.md.
 */
class DemoSeeder extends Seeder
{
    public const CLUB_SLUG = 'demo';

    public const DEMO_PASSWORD = 'demo1234';

    /** Usuarios del club: email => [nombre, rol(es), deportes (nombre)]. */
    public const CLUB_USERS = [
        'admin.club@sportiva.com' => ['Laura Gómez', ['admin'], []],
        'coordinador@sportiva.com' => ['Diego Herrera', ['sport_coordinator'], ['Básquet']],
        'coordinador.futbol@sportiva.com' => ['Pablo Ruiz', ['sport_coordinator'], ['Fútbol']],
        'profe@sportiva.com' => ['Martín Ledesma', ['coach'], []],
        'preparador@sportiva.com' => ['Sofía Bianchi', ['physical_trainer'], []],
        'utilero@sportiva.com' => ['Nicolás Paz', ['inventory_manager'], []],
        'mantenimiento@sportiva.com' => ['Ramiro Castro', ['maintenance_manager'], []],
    ];

    public function run(): void
    {
        if (Tenant::query()->where('slug', self::CLUB_SLUG)->exists()) {
            $this->command?->info('Los datos demo ya existen: no se vuelven a cargar.');

            return;
        }

        Model::unguard();

        try {
            $this->call(UserSeeder::class);
            $this->call(SupportTicketSeeder::class);

            $users = $this->createPlatformUsers();
            $tenant = $this->createClub();

            $clubUsers = $this->enrollClubUsers($tenant, $users);

            app(TenantDatabase::class)->run($tenant, function () use ($tenant, $clubUsers): void {
                app(ClubDemoSeeder::class)->run($tenant, $clubUsers);
            });

            $this->createPlatformActivity($users, $tenant);
            $this->createSessionsAndCache($users);
        } finally {
            Model::reguard();
        }
    }

    /** @return array<string, User> */
    private function createPlatformUsers(): array
    {
        $users = [];

        foreach (self::CLUB_USERS as $email => [$name]) {
            $users[$email] = User::query()->firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => Hash::make(self::DEMO_PASSWORD), 'is_active' => true, 'is_platform_admin' => false],
            );
        }

        // Usuario sin club: para mostrar la pantalla de "no tenés clubes asignados".
        $users['general@sportiva.com'] = User::query()->where('email', 'general@sportiva.com')->firstOrFail();

        return $users;
    }

    private function createClub(): Tenant
    {
        $tenant = Tenant::query()->create([
            'slug' => self::CLUB_SLUG,
            'name' => 'Club Demo Sportiva',
            'short_name' => 'CDS',
            'city' => 'Tigre, Buenos Aires',
            'founded_year' => 1985,
            'primary_color' => '#1d4ed8',
            'database_name' => 'club_'.self::CLUB_SLUG,
            'status' => 'provisioning',
        ]);

        // Mismo camino que el alta real de un club: crea la base, migra, carga roles y permisos.
        app()->call([app(ProvisionTenant::class, ['tenantId' => $tenant->id]), 'handle']);

        return $tenant->refresh();
    }

    /**
     * Membresía y perfil de club para cada usuario, con su rol y sus deportes.
     *
     * @param  array<string, User>  $users
     * @return array<string, int> email => club_user id
     */
    private function enrollClubUsers(Tenant $tenant, array $users): array
    {
        $ids = [];

        // Los deportes se cargan en ClubDemoSeeder: acá solo se asignan los roles.
        foreach (self::CLUB_USERS as $email => [$name, $roles]) {
            $user = $users[$email];
            TenantMembership::query()->firstOrCreate(['user_id' => $user->id, 'tenant_id' => $tenant->id], ['is_active' => true]);

            $ids[$email] = app(TenantDatabase::class)->run($tenant, fn (): int => app(ClubMembers::class)->ensure($user, $roles)->id);
        }

        return $ids;
    }

    /**
     * Registros de la plataforma: auditoría de altas y un lote de jobs completado.
     *
     * @param  array<string, User>  $users
     */
    private function createPlatformActivity(array $users, Tenant $tenant): void
    {
        $admin = User::query()->where('email', 'admin@sportiva.com')->firstOrFail();
        $now = now();

        $events = [
            ['action' => 'tenant.created', 'subject_type' => 'tenant', 'subject_id' => $tenant->id, 'changes' => ['name' => ['from' => null, 'to' => $tenant->name]]],
            ['action' => 'user.created', 'subject_type' => 'user', 'subject_id' => $users['admin.club@sportiva.com']->id, 'changes' => ['email' => ['from' => null, 'to' => 'admin.club@sportiva.com']]],
            ['action' => 'membership.created', 'subject_type' => 'membership', 'subject_id' => TenantMembership::query()->where('tenant_id', $tenant->id)->value('id') ?? 1, 'changes' => ['roles' => ['from' => [], 'to' => ['Administrador del club']]]],
            ['action' => 'auth.password_reset_requested', 'subject_type' => 'auth', 'subject_id' => $users['profe@sportiva.com']->id, 'changes' => null],
        ];

        foreach ($events as $event) {
            AuditLog::query()->create($event + ['actor_id' => $admin->id, 'ip' => '127.0.0.1', 'created_at' => $now->copy()->subDays(count($events))]);
        }

        // Un lote de jobs ya terminado (job_batches). La tabla jobs queda vacía a propósito: el worker la vacía.
        DB::table('job_batches')->insert([
            'id' => (string) Str::uuid(),
            'name' => 'demo:provision-club',
            'total_jobs' => 1,
            'pending_jobs' => 0,
            'failed_jobs' => 0,
            'failed_job_ids' => '[]',
            'options' => null,
            'cancelled_at' => null,
            'created_at' => $now->timestamp,
            'finished_at' => $now->timestamp,
        ]);
    }

    /** Sesiones, tokens y caché de ejemplo, para que esas tablas no estén vacías. */
    private function createSessionsAndCache(array $users): void
    {
        $admin = $users['admin.club@sportiva.com'];
        $now = now()->timestamp;

        foreach (['admin.club@sportiva.com', 'profe@sportiva.com'] as $email) {
            $users[$email]->createToken('demo-local', ['tenant:select', 'tenant:operate'], now()->addDays(30));
        }

        DB::table('sessions')->insert([
            'id' => Str::random(40),
            'user_id' => $admin->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Demo seed',
            'payload' => base64_encode(serialize([])),
            'last_activity' => $now,
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => 'profe@sportiva.com',
            'token' => Hash::make('demo-token-no-usar'),
            'created_at' => now(),
        ]);

        // Suscriptores del newsletter (para la pantalla de admin).
        foreach (['familia.perez@example.com', 'socios.norte@example.com', 'prensa.club@example.com', 'entrenador.libre@example.com', 'hincha.tigre@example.com'] as $email) {
            DB::table('newsletters')->insert(['email' => $email, 'created_at' => now(), 'updated_at' => now()]);
        }

        // La cola: un job programado muy a futuro (el worker no lo toma) y un fallo de ejemplo reintentable.
        ProvisionTenant::dispatch(Tenant::query()->where('slug', self::CLUB_SLUG)->value('id'))->delay(now()->addYears(10));
        $payload = DB::table('jobs')->orderByDesc('id')->value('payload');
        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => $payload,
            'exception' => "Demo: el alta del club tardó más de lo esperado (dato de ejemplo, reintentable).\n",
            'failed_at' => now(),
        ]);

        // Un lock de caché de ejemplo (la tabla cache_locks usa la app para bloqueos cortos).
        Cache::lock('demo:seed-lock', now()->addYears(10)->diffInSeconds(now()))->get();

        Cache::put('demo:seeded_at', now()->toIso8601String(), now()->addYear());
        Cache::put('demo:club_slug', self::CLUB_SLUG, now()->addYear());
    }
}
