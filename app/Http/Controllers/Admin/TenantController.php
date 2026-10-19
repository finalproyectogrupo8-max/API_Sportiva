<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexTenantRequest;
use App\Http\Requests\Admin\StoreTenantRequest;
use App\Http\Requests\Admin\UpdateTenantRequest;
use App\Http\Resources\Admin\TenantResource;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
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

    public function store(StoreTenantRequest $request): JsonResponse
    {
        Gate::authorize('create', Tenant::class);

        $slug = $request->validated('slug');

        $tenant = Tenant::create([
            'name' => $request->validated('name'),
            'slug' => $slug,
            'database_name' => 'club_'.$slug,
            'status' => TenantStatus::Provisioning->value,
        ]);

        return TenantResource::make($tenant)->response()->setStatusCode(201);
    }

    public function show(Tenant $tenant): TenantResource
    {
        Gate::authorize('view', $tenant);

        return TenantResource::make($tenant);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): TenantResource
    {
        Gate::authorize('update', $tenant);
        $tenant->update($request->validated());

        return TenantResource::make($tenant);
    }

    public function destroy(Tenant $tenant): Response
    {
        Gate::authorize('delete', $tenant);
        $tenant->delete();

        return response()->noContent();
    }
}
