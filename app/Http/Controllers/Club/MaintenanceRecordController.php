<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Http\Requests\Club\IndexMaintenanceRecordRequest;
use App\Http\Requests\Club\StoreMaintenanceRecordRequest;
use App\Http\Requests\Club\UpdateMaintenanceRecordRequest;
use App\Http\Resources\Club\MaintenanceRecordResource;
use App\Models\Club\MaintenanceRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class MaintenanceRecordController extends Controller
{
    public function index(IndexMaintenanceRecordRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', MaintenanceRecord::class);

        $filters = $request->validated();
        $sort = $filters['sort'] ?? '-performed_at';
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';

        $records = MaintenanceRecord::query()
            ->with(['facility', 'repairRequest'])
            ->when($filters['facility_id'] ?? null, fn ($q, $id) => $q->where('facility_id', $id))
            ->when($filters['repair_request_id'] ?? null, fn ($q, $id) => $q->where('repair_request_id', $id))
            ->when($filters['performed_by'] ?? null, fn ($q, $id) => $q->where('performed_by', $id))
            ->when($filters['from_date'] ?? null, fn ($q, $date) => $q->whereDate('performed_at', '>=', $date))
            ->when($filters['to_date'] ?? null, fn ($q, $date) => $q->whereDate('performed_at', '<=', $date))
            ->orderBy('performed_at', $direction)
            ->orderBy('id', $direction)
            ->paginate($filters['per_page'] ?? 20);

        return MaintenanceRecordResource::collection($records);
    }

    public function store(StoreMaintenanceRecordRequest $request): JsonResponse
    {
        Gate::authorize('create', MaintenanceRecord::class);

        $record = MaintenanceRecord::create($request->validated());

        return MaintenanceRecordResource::make($record->refresh()->load(['facility', 'repairRequest']))
            ->response()
            ->setStatusCode(201);
    }

    public function show(MaintenanceRecord $maintenanceRecord): MaintenanceRecordResource
    {
        Gate::authorize('view', $maintenanceRecord);

        return MaintenanceRecordResource::make($maintenanceRecord->load(['facility', 'repairRequest']));
    }

    public function update(UpdateMaintenanceRecordRequest $request, MaintenanceRecord $maintenanceRecord): MaintenanceRecordResource
    {
        Gate::authorize('update', $maintenanceRecord);

        $maintenanceRecord->update($request->validated());

        return MaintenanceRecordResource::make($maintenanceRecord->refresh()->load(['facility', 'repairRequest']));
    }

    public function destroy(MaintenanceRecord $maintenanceRecord): Response
    {
        Gate::authorize('delete', $maintenanceRecord);

        $maintenanceRecord->delete();

        return response()->noContent();
    }
}
