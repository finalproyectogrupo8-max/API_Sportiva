<?php

namespace App\Console\Commands;

use App\Enums\MembershipRole;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Tenancy\TenantDatabase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Throwable;

class SetupLocalEnvironment extends Command
{
    protected $signature = 'app:setup-local 
                            {--email=admin@sportiva.test : Email del administrador} 
                            {--name=Admin : Nombre del administrador} 
                            {--password=password12345 : Contraseña del administrador (mínimo 12 caracteres)} 
                            {--club-name=Club Atlético Sportiva : Nombre del club de prueba} 
                            {--club-slug=sportiva : Slug del club de prueba}';

    protected $description = 'Configura el entorno local creando un admin de plataforma, club activo, membresía y base de datos provisionada.';

    public function handle(TenantDatabase $tenantDatabase): int
    {
        $email = (string) $this->option('email');
        $name = (string) $this->option('name');
        $password = (string) $this->option('password');
        $clubName = (string) $this->option('club-name');
        $clubSlug = (string) $this->option('club-slug');

        $this->info('==> [1/4] Creando o verificando usuario administrador de plataforma...');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $exitCode = $this->call('platform:create-admin', [
                'email' => $email,
                '--name' => $name,
                '--password' => $password,
            ]);

            if ($exitCode !== self::SUCCESS) {
                $this->error('No se pudo crear el usuario administrador.');

                return self::FAILURE;
            }

            $user = User::where('email', $email)->firstOrFail();
        } else {
            $user->name = $name;
            $user->password = Hash::make($password);
            $user->is_platform_admin = true;
            $user->is_active = true;
            $user->save();
            $this->line("Usuario [{$email}] ya existía; se aseguró su rol de platform admin y contraseña.");
        }

        $this->info("==> [2/4] Creando club de prueba [{$clubName}] ({$clubSlug})...");

        /** @var Tenant $tenant */
        $tenant = Tenant::withTrashed()->firstOrNew(['slug' => $clubSlug]);
        if ($tenant->trashed()) {
            $tenant->restore();
        }

        $tenant->name = $clubName;
        $tenant->slug = $clubSlug;
        $tenant->database_name = 'club_'.$clubSlug;
        // Forzamos el estado activo para permitir select-tenant
        $tenant->status = TenantStatus::Active->value;
        $tenant->save();

        $this->info("Club configurado en estado [{$tenant->status}] con base [{$tenant->database_name}].");

        $this->info('==> [3/4] Vinculando membresía activa de administrador...');

        TenantMembership::updateOrCreate(
            [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
            ],
            [
                'role' => MembershipRole::Admin->value,
                'is_active' => true,
            ]
        );

        $this->info("Membresía vinculada con rol [admin] para [{$user->email}] en [{$tenant->slug}].");

        $this->info("==> [4/4] Creando y migrando base de datos del club [{$tenant->database_name}]...");

        try {
            $tenantDatabase->create($tenant);
            $this->line("Base de datos [{$tenant->database_name}] creada (o existente).");

            $tenantDatabase->migrate($tenant);
            $this->line("Migraciones de club ejecutadas exitosamente en [{$tenant->database_name}].");
        } catch (Throwable $e) {
            $this->error("Error al provisionar la base de datos del club: {$e->getMessage()}");
            $this->warn('Verificá que el usuario de MySQL tenga permisos sobre club_* (ver docs/desarrollo-local.md).');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('✔ Setup local completado exitosamente.');
        $this->table(
            ['Clave', 'Valor'],
            [
                ['Email Admin', $email],
                ['Password Admin', $password],
                ['Platform Admin', 'Sí (is_platform_admin = true)'],
                ['Club Slug', $clubSlug],
                ['Club Base de Datos', $tenant->database_name],
                ['Club Estado', $tenant->status],
            ]
        );

        return self::SUCCESS;
    }
}

