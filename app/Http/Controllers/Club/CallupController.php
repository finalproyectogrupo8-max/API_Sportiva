<?php

namespace App\Http\Controllers\Club;

use App\Club\Callups\CallupService;
use App\Http\Controllers\Club\Concerns\ChecksClubScope;
use App\Http\Controllers\Controller;
use App\Http\Resources\Club\CallupSheetResource;
use App\Models\Club\GameMatch;
use Illuminate\Http\Request;

/**
 * Convocatoria de un partido (TIZ-33): el profe convoca entre los habilitados, publica y comparte
 * por WhatsApp; cada jugador confirma desde su link personal.
 */
class CallupController extends Controller
{
    use ChecksClubScope;

    public function __construct(private CallupService $callups) {}

    public function show(Request $request, GameMatch $match): CallupSheetResource
    {
        $this->authorizeClub('callups.view');
        $this->ensureCategoryInScope($match->category, 'callups.view');

        return CallupSheetResource::make($this->callups->sheet($match, $request->attributes->get('tenant')));
    }

    /** Reemplaza la lista. `players`: `[{player_id, shirt_number?}]`. */
    public function update(Request $request, GameMatch $match): CallupSheetResource
    {
        $this->authorizeClub('callups.update');
        $this->ensureCategoryInScope($match->category, 'callups.update');
        $data = $request->validate([
            'players' => ['present', 'array', 'max:60'],
            'players.*.player_id' => ['required', 'integer', 'distinct'],
            'players.*.shirt_number' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $tenant = $request->attributes->get('tenant');
        $this->callups->update($match, $tenant, $data['players']);

        return CallupSheetResource::make($this->callups->sheet($match, $tenant));
    }

    /** Publica (o vuelve a publicar después de un cambio). Genera la página pública y los mensajes. */
    public function publish(Request $request, GameMatch $match): CallupSheetResource
    {
        $this->authorizeClub('callups.publish');
        $this->ensureCategoryInScope($match->category, 'callups.publish');

        $tenant = $request->attributes->get('tenant');
        $this->callups->publish($match, $tenant);

        return CallupSheetResource::make($this->callups->sheet($match->refresh(), $tenant));
    }
}
