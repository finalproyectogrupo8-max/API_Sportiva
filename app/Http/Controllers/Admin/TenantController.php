<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CreateTenantAction;
use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexTenantRequest;
use App\Http\Requests\Admin\StoreTenantRequest;
use App\Http\Requests\Admin\UpdateTenantRequest;
use App\Http\Resources\Admin\TenantResource;
use App\Jobs\ProvisionTenant;
use App\Models\Tenant;
use App\Services\AuditLogger;
use App\Support\ApiError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TenantController extends Controller
{
    /**
     * Lista paginada de clubes. Filtros opcionales: ?status=active&search=river
     */
    public function index(IndexTenantRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Tenant::class);

        $sort = $request->validated('sort', 'name');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $tenants = Tenant::query()
            ->when($request->validated('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->validated('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy(ltrim($sort, '-'), $direction)
            ->orderBy('id')
            ->paginate(min(max((int) $request->validated('per_page', 20), 1), 100));

        return TenantResource::collection($tenants);
    }

    public function store(StoreTenantRequest $request, CreateTenantAction $createTenant, AuditLogger $auditLogger): JsonResponse
    {
        Gate::authorize('create', Tenant::class);

        $tenant = $createTenant->execute(
            $request->validated('name'),
            $request->validated('slug'),
            $auditLogger,
            $request->validated('admin'),
            $request->safe()->only(Tenant::PROFILE_FIELDS),
        );

        return TenantResource::make($tenant->refresh())->response()->setStatusCode(201);
    }

    public function retry(Tenant $tenant, AuditLogger $auditLogger): JsonResponse|TenantResource
    {
        Gate::authorize('retry', $tenant);

        if ($tenant->status !== TenantStatus::Failed->value) {
            return ApiError::conflict('tenant_not_failed');
        }

        DB::transaction(function () use ($tenant, $auditLogger) {
            $tenant->status = TenantStatus::Provisioning->value;
            $auditLogger->recordUpdated($tenant);
            $tenant->save();

            ProvisionTenant::dispatch($tenant->id)->afterCommit();
        });

        return TenantResource::make($tenant->refresh());
    }

    public function show(Tenant $tenant): TenantResource
    {
        Gate::authorize('view', $tenant);

        return TenantResource::make($tenant);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant, AuditLogger $auditLogger): TenantResource
    {
        Gate::authorize('update', $tenant);

        DB::transaction(function () use ($request, $tenant, $auditLogger) {
            $tenant->fill($request->validated());
            $auditLogger->recordUpdated($tenant);
            $tenant->save();
        });

        return TenantResource::make($tenant);
    }

    public function destroy(Tenant $tenant, AuditLogger $auditLogger): Response
    {
        Gate::authorize('delete', $tenant);

        DB::transaction(function () use ($tenant, $auditLogger) {
            $auditLogger->recordDeleted($tenant);
            $tenant->delete();
        });

        return response()->noContent();
    }
}
