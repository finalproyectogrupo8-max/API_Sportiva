<?php

namespace App\Actions\Admin;

use App\Enums\TenantStatus;
use App\Jobs\ProvisionTenant;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class CreateTenantAction
{
    /**
     * @param  array{name: string, email: string, password: string}|null  $admin  Si viene, se crea ese
     *                                                                            usuario y se lo asocia al club en la misma transacción. ProvisionTenant le asigna el rol
     *                                                                            "Administrador del club" cuando crea la base.
     * @param  array<string, mixed>  $profile  Datos del perfil (Tenant::PROFILE_FIELDS).
     */
    public function execute(
        string $name,
        string $slug,
        ?AuditLogger $auditLogger = null,
        ?array $admin = null,
        array $profile = [],
    ): Tenant {
        return DB::transaction(function () use ($name, $slug, $auditLogger, $admin, $profile) {
            $tenant = Tenant::create([
                ...array_intersect_key($profile, array_flip(Tenant::PROFILE_FIELDS)),
                'name' => $name,
                'slug' => $slug,
                'database_name' => 'club_'.$slug,
                'status' => TenantStatus::Provisioning->value,
            ]);

            $auditLogger?->recordCreated($tenant);

            if ($admin !== null) {
                $user = new User($admin);
                $user->is_active = true;
                $user->is_platform_admin = false;
                $user->save();
                $auditLogger?->recordCreated($user);

                $membership = TenantMembership::create([
                    'user_id' => $user->id,
                    'tenant_id' => $tenant->id,
                    'is_active' => true,
                ]);
                $auditLogger?->recordCreated($membership);
            }

            ProvisionTenant::dispatch($tenant->id)->afterCommit();

            return $tenant;
        });
    }
}
