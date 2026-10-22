<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexUserRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\Admin\UserResource;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(IndexUserRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', User::class);

        $sort = $request->validated('sort', 'name');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $filters = $request->validated();

        $users = User::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(
                fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
            ))
            ->when(
                array_key_exists('is_active', $filters),
                fn ($query) => $query->where('is_active', $filters['is_active'])
            )
            ->orderBy(ltrim($sort, '-'), $direction)
            ->orderBy('id')
            ->paginate(min(max((int) ($filters['per_page'] ?? 20), 1), 100));

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request, AuditLogger $auditLogger): JsonResponse
    {
        Gate::authorize('create', User::class);

        $user = DB::transaction(function () use ($request, $auditLogger) {
            $user = new User($request->validated());
            $user->is_active = true;
            $user->is_platform_admin = false;
            $user->save();

            $auditLogger->recordCreated($user);

            return $user;
        });

        return UserResource::make($user)->response()->setStatusCode(201);
    }

    public function show(User $user): UserResource
    {
        Gate::authorize('view', $user);

        return UserResource::make($user);
    }

    public function update(
        UpdateUserRequest $request,
        User $user,
        AuditLogger $auditLogger,
    ): UserResource {
        Gate::authorize('update', $user);

        DB::transaction(function () use ($request, $user, $auditLogger) {
            $validated = $request->validated();
            $wasActive = $user->is_active;
            $passwordChanged = array_key_exists('password', $validated)
                && ! Hash::check($validated['password'], $user->getRawOriginal('password'));

            $attributes = array_intersect_key($validated, array_flip(['name', 'email']));
            if ($passwordChanged) {
                $attributes['password'] = $validated['password'];
            }

            $user->fill($attributes);

            if (array_key_exists('is_active', $validated)) {
                $user->is_active = $validated['is_active'];
            }

            $auditLogger->recordUpdated($user);
            $user->save();

            if (($wasActive && ! $user->is_active) || $passwordChanged) {
                $user->tokens()->delete();
            }
        });

        return UserResource::make($user);
    }
}
