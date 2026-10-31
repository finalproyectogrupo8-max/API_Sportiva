<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Http\Requests\Club\UpdateClubRequest;
use App\Http\Resources\Club\ClubResource;
use App\Models\Club\Sport;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/** Perfil del club del token (TIZ-13). Lo ve cualquier miembro y lo edita quien tenga club.update. */
class ClubController extends Controller
{
    public function show(Request $request): ClubResource
    {
        $tenant = $this->tenant($request);

        return new ClubResource($tenant, $this->stats());
    }

    public function update(UpdateClubRequest $request, AuditLogger $auditLogger): ClubResource
    {
        Gate::allowIf(fn (User $user) => $user->hasClubPermission('club.update'));

        $tenant = $this->tenant($request);

        // La base central no forma parte de la conexión del club: transacción aparte.
        DB::connection($tenant->getConnectionName())->transaction(function () use ($request, $tenant, $auditLogger) {
            $tenant->fill($request->validated());
            $auditLogger->recordUpdated($tenant);
            $tenant->save();
        });

        return new ClubResource($tenant, $this->stats());
    }

    private function tenant(Request $request): Tenant
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('tenant');

        return $tenant;
    }

    /**
     * La tabla players existe en la base del club, pero su modelo llega con TIZ-15.
     *
     * @return array{active_players: int, sports: list<string>}
     */
    private function stats(): array
    {
        return [
            'active_players' => DB::connection('tenant')->table('players')
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->count(),
            'sports' => Sport::query()->where('is_active', true)->orderBy('name')->pluck('name')->values()->all(),
        ];
    }
}
