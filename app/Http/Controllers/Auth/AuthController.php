<?php

namespace App\Http\Controllers\Auth;

use App\Club\Access\ClubAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\SelectTenantRequest;
use App\Http\Resources\Auth\LoginResource;
use App\Http\Resources\Auth\MeResource;
use App\Http\Resources\Auth\MeTenantsResource;
use App\Http\Resources\Auth\SelectTenantResource;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\ApiError;
use App\Tenancy\TenantDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Envía las instrucciones para restablecer la contraseña si el usuario existe y está activo.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $email = $request->validated('email');

        defer(function () use ($email) {
            $user = User::where('email', $email)->first();

            if ($user && $user->is_active) {
                $token = Password::broker()->createToken($user);
                $user->sendPasswordResetNotification($token);
            }
        });

        return response()->json([
            'message' => 'Si tu correo electrónico está registrado y activo, recibirás las instrucciones para restablecer tu contraseña.',
        ], 202);
    }

    /**
     * Restablece la contraseña utilizando el token de recuperación recibido por correo.
     */
    public function resetPassword(ResetPasswordRequest $request, AuditLogger $auditLogger): JsonResponse
    {
        $credentials = $request->only('email', 'token', 'password', 'password_confirmation');

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['El enlace para restablecer la contraseña es inválido o ha expirado.'],
            ]);
        }

        $status = Password::broker()->reset(
            $credentials,
            function (User $user, string $password) use ($auditLogger) {
                $user->password = $password;
                $auditLogger->recordUpdated($user);
                $user->save();

                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => ['El enlace para restablecer la contraseña es inválido o ha expirado.'],
            ]);
        }

        return response()->json([
            'message' => 'Contraseña restablecida con éxito.',
        ]);
    }

    /**
     * Valida credenciales contra la base central y emite un token pre-tenant.
     */
    public function login(LoginRequest $request): LoginResource|JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        $hash = $user?->password ?? Hash::make(Str::random(32));

        if (! Hash::check($request->validated('password'), $hash) || ! $user || ! $user->is_active) {
            return response()->json(['message' => 'Credenciales inválidas.'], 401);
        }

        $token = $user->createToken(
            'pre-tenant',
            ['tenant:select'],
            now()->addMinutes(config('sanctum.pre_tenant_ttl')),
        );

        $platformToken = null;
        if ($user->is_platform_admin) {
            $platformToken = $user->createToken(
                'platform',
                ['platform:admin'],
                now()->addMinutes(config('sanctum.platform_ttl')),
            );
        }

        return new LoginResource($token, $user, $this->activeTenantsFor($user), $platformToken);
    }

    /**
     * Revalida la membresía y cambia el token actual por uno atado al club.
     */
    public function selectTenant(SelectTenantRequest $request, TenantDatabase $tenantDatabase): SelectTenantResource|JsonResponse
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

        // Los roles quedan cargados (eager loading): el resource no vuelve a consultar la base del club.
        $access = $tenantDatabase->run($tenant, fn () => ClubAccess::forGlobalUser($user->id));

        if ($access->clubUser && ! $access->clubUser->isActive()) {
            return ApiError::forbidden('club_user_disabled');
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

        return new SelectTenantResource($new, $tenant, $access);
    }

    public function me(Request $request): MeResource
    {
        return new MeResource(
            $request->user(),
            $request->attributes->get('tenant'),
            $request->user()->clubAccess(),
        );
    }

    /**
     * Clubes disponibles para el selector. Sirve con cualquier token del usuario.
     */
    public function meTenants(Request $request): MeTenantsResource
    {
        return new MeTenantsResource($request->user(), $this->activeTenantsFor($request->user()));
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
