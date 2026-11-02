<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\TenantSummaryResource;
use App\Models\Club\ClubUser;
use App\Models\Tenant;
use App\Tenancy\TenantDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * "Mi perfil" (FRO-28): el usuario cambia su nombre y su contraseña, ve sus clubes con su rol en
 * cada uno y cierra las demás sesiones. Sirve con cualquier token (de club, previo al club o de plataforma).
 */
class MeController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $user = $request->user();
        $user->update(['name' => trim($data['name'])]);

        return response()->json(['data' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email]]);
    }

    /** Cambia la contraseña con la actual. Cierra las demás sesiones: la actual sigue abierta. */
    public function password(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:12', 'max:255', 'confirmed', 'different:current_password'],
        ], [
            'password.different' => 'La contraseña nueva tiene que ser distinta de la actual.',
        ]);

        $user = $request->user();
        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'La contraseña actual no es correcta.']);
        }

        $user->forceFill(['password' => Hash::make($data['password'])])->save();
        $closed = $this->closeOtherSessions($request);

        return response()->json(['data' => ['closed_sessions' => $closed]]);
    }

    /** Cierra la sesión en todos los demás dispositivos. */
    public function logoutAll(Request $request): JsonResponse
    {
        return response()->json(['data' => ['closed_sessions' => $this->closeOtherSessions($request)]]);
    }

    /** Clubes activos del usuario con sus roles en cada uno. */
    public function clubs(Request $request, TenantDatabase $tenantDatabase): JsonResponse
    {
        $user = $request->user();
        $tenants = Tenant::query()
            ->where('status', 'active')
            ->whereHas('memberships', fn ($q) => $q->where('user_id', $user->id)->where('is_active', true))
            ->orderBy('name')
            ->get();

        $clubs = $tenants->map(function (Tenant $tenant) use ($tenantDatabase, $user) {
            try {
                $roles = $tenantDatabase->run($tenant, fn () => ClubUser::query()
                    ->where('global_user_id', $user->id)
                    ->with('roles')
                    ->first()?->roles->pluck('name')->values()->all() ?? []);
            } catch (\Throwable) {
                // Si la base de un club no responde, el club se lista igual, sin roles.
                $roles = [];
            }

            return [
                ...(new TenantSummaryResource($tenant))->resolve(),
                /** Nombres de los roles en el club. */
                'roles' => $roles,
            ];
        });

        return response()->json(['data' => $clubs->values()]);
    }

    private function closeOtherSessions(Request $request): int
    {
        $current = $request->user()->currentAccessToken();

        return $request->user()->tokens()->when($current?->id, fn ($q, $id) => $q->whereKeyNot($id))->delete();
    }
}
