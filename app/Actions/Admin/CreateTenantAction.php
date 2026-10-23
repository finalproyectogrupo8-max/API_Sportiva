<?php

namespace App\Actions\Admin;

use App\Enums\TenantStatus;
use App\Jobs\ProvisionTenant;
use App\Models\Tenant;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class CreateTenantAction
{
    public function execute(string $name, string $slug, ?AuditLogger $auditLogger = null): Tenant
    {
        return DB::transaction(function () use ($name, $slug, $auditLogger) {
            $tenant = Tenant::create([
                'name' => $name,
                'slug' => $slug,
                'database_name' => 'club_'.$slug,
                'status' => TenantStatus::Provisioning->value,
            ]);

            $auditLogger?->recordCreated($tenant);

            ProvisionTenant::dispatch($tenant->id)->afterCommit();

            return $tenant;
        });
    }
}
