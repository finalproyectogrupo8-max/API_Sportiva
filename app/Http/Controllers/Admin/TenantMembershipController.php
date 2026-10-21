<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTenantMembershipRequest;
use App\Http\Requests\Admin\UpdateTenantMembershipRequest;
use App\Http\Resources\Admin\TenantMembershipResource;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TenantMembershipController extends Controller
{
    public function store(
        StoreTenantMembershipRequest $request,
        Tenant $tenant,
        AuditLogger $auditLogger,
    ): JsonResponse {
        Gate::authorize('create', TenantMembership::class);

        $membership = DB::transaction(function () use ($request, $tenant, $auditLogger) {
            $membership = TenantMembership::create([
                ...$request->validated(),
                'tenant_id' => $tenant->id,
                'is_active' => true,
            ]);

            $auditLogger->recordCreated($membership);

            return $membership;
        });

        return TenantMembershipResource::make($membership)->response()->setStatusCode(201);
    }

    public function update(
        UpdateTenantMembershipRequest $request,
        Tenant $tenant,
        TenantMembership $membership,
        AuditLogger $auditLogger,
    ): TenantMembershipResource {
        Gate::authorize('update', $membership);

        DB::transaction(function () use ($request, $tenant, $membership, $auditLogger) {
            $wasActive = $membership->is_active;
            $validated = $request->validated();
            $membership->fill(array_intersect_key($validated, ['role' => true]));

            if (array_key_exists('is_active', $validated)) {
                $membership->is_active = $validated['is_active'];
            }

            $auditLogger->recordUpdated($membership);
            $membership->save();

            if ($wasActive && ! $membership->is_active) {
                $membership->user->tokens()
                    ->where('tenant_id', $tenant->id)
                    ->delete();
            }
        });

        return TenantMembershipResource::make($membership);
    }
}
