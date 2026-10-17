<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\SelectTenantRequest;
use App\Http\Resources\AuthSessionResource;
use App\Http\Resources\TenantSummaryResource;
use App\Http\Resources\UserResource;
use App\Models\Tenant;
use App\Models\User;
use App\Support\ApiError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Valida credenciales contra la base central y emite un token pre-tenant.
     */
    public function login(LoginRequest $request): AuthSessionResource|JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        $hash = $user?->password ?? Hash::make(Str::random(32));

        if (! Hash::check($request->validated('password'), $hash) || ! $user) {
            return response()->json(['message' => 'Credenciales inválidas.'], 401);
        }

        $token = $user->createToken(
            'pre-tenant',
            ['tenant:select'],
            now()->addMinutes(config('sanctum.pre_tenant_ttl')),
        );

        return new AuthSessionResource([
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at->toIso8601String(),
            'user' => new UserResource($user),
            'tenants' => TenantSummaryResource::collection($this->activeTenantsFor($user)),
        ]);
    }

    /**
     * Revalida la membresía y cambia el token actual por uno atado al club.
     */
    public function selectTenant(SelectTenantRequest $request): AuthSessionResource|JsonResponse
    {
        $user = $request->user();
        $tenant = Tenant::where('slug', $request->validated('tenant'))->first();

        $hasMembership = $tenant && $user->tenantMemberships()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->exists();

        if (! $hasMembership) {
            return ApiError::forbidden('membership_inactive');
        }

        if ($tenant->status !== 'active') {
            return ApiError::forbidden('tenant_inactive');
        }

        $new = DB::transaction(function () use ($user, $tenant) {
            $user->currentAccessToken()->delete();

            $new = $user->createToken(
                'tenant:'.$tenant->slug,
                ['tenant:select', 'tenant:operate'],
                now()->addMinutes(config('sanctum.tenant_ttl')),
            );
            $new->accessToken->forceFill(['tenant_id' => $tenant->id])->save();

            return $new;
        });

        return new AuthSessionResource([
            'token' => $new->plainTextToken,
            'expires_at' => $new->accessToken->expires_at->toIso8601String(),
            'tenant' => new TenantSummaryResource($tenant),
            'profile' => null,
            'permissions' => [],
        ]);
    }

    public function me(Request $request): AuthSessionResource
    {
        return new AuthSessionResource([
            'user' => new UserResource($request->user()),
            'tenant' => new TenantSummaryResource($request->attributes->get('tenant')),
            'profile' => null,
            'permissions' => [],
        ]);
    }

    /**
     * Clubes disponibles para el selector. Sirve con cualquier token del usuario.
     */
    public function meTenants(Request $request): AuthSessionResource
    {
        return new AuthSessionResource([
            'tenants' => TenantSummaryResource::collection($this->activeTenantsFor($request->user())),
        ]);
    }

    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    /** @return Collection<int, Tenant> */
    private function activeTenantsFor(User $user): Collection
    {
        return Tenant::query()
            ->where('status', 'active')
            ->whereHas('memberships', fn ($q) => $q
                ->where('user_id', $user->id)
                ->where('is_active', true))
            ->orderBy('name')
            ->get();
    }
}
