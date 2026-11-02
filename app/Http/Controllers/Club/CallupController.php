<?php

namespace App\Http\Controllers\Club;

use App\Club\Callups\CallupNoticeMode;
use App\Club\Callups\CallupService;
use App\Http\Controllers\Club\Concerns\ChecksClubScope;
use App\Http\Controllers\Controller;
use App\Http\Resources\Club\CallupSheetResource;
use App\Jobs\Club\NotifyCallup;
use App\Models\Club\GameMatch;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Convocatoria de un partido (TIZ-33): el profe convoca entre los habilitados, publica y comparte
 * por WhatsApp y por mail (TIZ-37); cada jugador confirma desde su link personal.
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

    /**
     * Publica (o vuelve a publicar después de un cambio). Genera la página pública y los mensajes, y
     * encola el aviso por mail a los contactos principales (TIZ-37).
     */
    public function publish(Request $request, GameMatch $match): CallupSheetResource
    {
        $this->authorizeClub('callups.publish');
        $this->ensureCategoryInScope($match->category, 'callups.publish');

        $tenant = $request->attributes->get('tenant');
        $mode = $this->callups->publish($match, $tenant);
        NotifyCallup::dispatch($tenant->id, $match->id, $mode, now()->toIso8601String())->afterCommit();

        return CallupSheetResource::make($this->callups->sheet($match->refresh(), $tenant));
    }

    /** Reenvía el aviso por mail solo a los convocados que siguen sin responder (TIZ-37). */
    public function remind(Request $request, GameMatch $match): CallupSheetResource
    {
        $this->authorizeClub('callups.publish');
        $this->ensureCategoryInScope($match->category, 'callups.publish');

        if ($match->callup_published_at === null) {
            throw ValidationException::withMessages(['callup' => 'Publicá la convocatoria antes de mandar un recordatorio.']);
        }

        $tenant = $request->attributes->get('tenant');
        NotifyCallup::dispatch($tenant->id, $match->id, CallupNoticeMode::Reminder, now()->toIso8601String())->afterCommit();

        return CallupSheetResource::make($this->callups->sheet($match->refresh(), $tenant));
    }
}
