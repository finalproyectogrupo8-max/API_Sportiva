<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Http\Requests\Club\AssignRepairRequestRequest;
use App\Http\Requests\Club\IndexRepairRequestRequest;
use App\Http\Requests\Club\ResolveRepairRequestRequest;
use App\Http\Requests\Club\StoreRepairRequestRequest;
use App\Http\Requests\Club\UpdateRepairRequestRequest;
use App\Http\Resources\Club\RepairRequestResource;
use App\Models\Club\ClubUser;
use App\Models\Club\MaintenanceRecord;
use App\Models\Club\RepairRequest;
use App\Support\ApiError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RepairRequestController extends Controller
{
    public function index(IndexRepairRequestRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', RepairRequest::class);

        $filters = $request->validated();
        $sort = $filters['sort'] ?? '-reported_at';
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';

        $requests = RepairRequest::query()
            ->with(['facility', 'component'])
            ->when($filters['facility_id'] ?? null, fn ($q, $id) => $q->where('facility_id', $id))
            ->when($filters['component_id'] ?? null, fn ($q, $id) => $q->where('component_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['priority'] ?? null, fn ($q, $priority) => $q->where('priority', $priority))
            ->when($filters['assigned_to'] ?? null, fn ($q, $id) => $q->where('assigned_to', $id))
            ->when($filters['reported_by'] ?? null, fn ($q, $id) => $q->where('reported_by', $id))
            ->orderBy('reported_at', $direction)
            ->orderBy('id', $direction)
            ->paginate($filters['per_page'] ?? 20);

        return RepairRequestResource::collection($requests);
    }

    public function store(StoreRepairRequestRequest $request): JsonResponse
    {
        Gate::authorize('create', RepairRequest::class);

        $clubUserId = $request->user()->clubAccess()->clubUser?->id
            ?? ClubUser::where('global_user_id', $request->user()->id)->value('id');

        if (! $clubUserId) {
            $clubUser = ClubUser::create([
                'global_user_id' => $request->user()->id,
                'display_name' => $request->user()->name,
                'status' => 'active',
            ]);
            $clubUserId = $clubUser->id;
        }

        $data = $request->validated();
        $status = ! empty($data['assigned_to']) ? 'assigned' : 'open';

        $repairRequest = DB::connection('tenant')->transaction(function () use ($data, $clubUserId, $status) {
            $created = RepairRequest::create([
                'facility_id' => $data['facility_id'],
                'component_id' => $data['component_id'] ?? null,
                'reported_by' => $clubUserId,
                'assigned_to' => $data['assigned_to'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'],
                'priority' => $data['priority'] ?? 'normal',
                'status' => $status,
                'reported_at' => now(),
            ]);

            if (! empty($data['component_id']) && ! empty($data['component_condition_status'])) {
                $created->component?->update([
                    'condition_status' => $data['component_condition_status'],
                    'last_inspected_at' => now(),
                ]);
            }

            return $created;
        });

        return RepairRequestResource::make($repairRequest->refresh()->load(['facility', 'component']))
            ->response()
            ->setStatusCode(201);
    }

    public function show(RepairRequest $repairRequest): RepairRequestResource
    {
        Gate::authorize('view', $repairRequest);

        return RepairRequestResource::make($repairRequest->load(['facility', 'component', 'maintenanceRecord']));
    }

    public function update(UpdateRepairRequestRequest $request, RepairRequest $repairRequest): RepairRequestResource
    {
        Gate::authorize('update', $repairRequest);

        $repairRequest->update($request->validated());

        return RepairRequestResource::make($repairRequest->refresh()->load(['facility', 'component', 'maintenanceRecord']));
    }

    public function destroy(RepairRequest $repairRequest): Response
    {
        Gate::authorize('delete', $repairRequest);

        $repairRequest->delete();

        return response()->noContent();
    }

    public function assign(AssignRepairRequestRequest $request, RepairRequest $repairRequest): RepairRequestResource|JsonResponse
    {
        Gate::authorize('assign', $repairRequest);

        if ($repairRequest->status === 'resolved') {
            return ApiError::conflict('repair_already_resolved');
        }

        if ($repairRequest->status === 'cancelled') {
            return ApiError::conflict('repair_cancelled');
        }

        $repairRequest->update([
            'assigned_to' => $request->validated('assigned_to'),
            'status' => 'assigned',
        ]);

        return RepairRequestResource::make($repairRequest->refresh()->load(['facility', 'component']));
    }

    public function resolve(ResolveRepairRequestRequest $request, RepairRequest $repairRequest): RepairRequestResource|JsonResponse
    {
        Gate::authorize('resolve', $repairRequest);

        if ($repairRequest->status === 'resolved') {
            return ApiError::conflict('repair_already_resolved');
        }

        if ($repairRequest->status === 'cancelled') {
            return ApiError::conflict('repair_cancelled');
        }

        if ($repairRequest->assigned_to === null) {
            return ApiError::conflict('repair_not_assigned');
        }

        $data = $request->validated();
        $performedBy = $data['performed_by'] ?? $repairRequest->assigned_to;
        $performedAt = $data['performed_at'] ?? now();

        DB::connection('tenant')->transaction(function () use ($repairRequest, $data, $performedBy, $performedAt) {
            $repairRequest->update([
                'status' => 'resolved',
                'resolved_at' => now(),
            ]);

            if ($repairRequest->component) {
                $repairRequest->component->update([
                    'condition_status' => 'ok',
                    'last_inspected_at' => now(),
                ]);
            }

            MaintenanceRecord::create([
                'repair_request_id' => $repairRequest->id,
                'facility_id' => $repairRequest->facility_id,
                'performed_by' => $performedBy,
                'performed_at' => $performedAt,
                'description' => $data['description'],
                'cost' => $data['cost'] ?? null,
            ]);
        });

        return RepairRequestResource::make($repairRequest->refresh()->load(['facility', 'component', 'maintenanceRecord']));
    }
}
