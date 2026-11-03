<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Club\Concerns\ChecksClubScope;
use App\Http\Controllers\Controller;
use App\Models\Club\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Registro de actividad del club (FRO-30): quién aprobó un pedido, cambió un rol, dio de baja un
 * jugador o mandó un comunicado. Lo ve quien tiene `audit.view` (por defecto, el administrador).
 */
class AuditLogController extends Controller
{
    use ChecksClubScope;

    public function index(Request $request): JsonResponse
    {
        $this->authorizeClub('audit.view');
        $filters = $request->validate([
            /** Prefijo de la acción: `order`, `player`, `club_user`, `announcement`. */
            'type' => ['sometimes', 'nullable', 'string', 'max:40'],
            'club_user_id' => ['sometimes', 'nullable', 'integer'],
            'from' => ['sometimes', 'nullable', 'date'],
            'to' => ['sometimes', 'nullable', 'date'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $page = AuditLog::query()
            ->with('actor:id,display_name')
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('action', 'like', $type.'.%'))
            ->when($filters['club_user_id'] ?? null, fn ($q, $id) => $q->where('club_user_id', $id))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->where('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->where('created_at', '<', date('Y-m-d', strtotime($to.' +1 day'))))
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 30);

        return response()->json([
            'data' => collect($page->items())->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'entity_type' => $log->entity_type,
                'entity_id' => $log->entity_id,
                'actor' => $log->actor ? ['id' => $log->actor->id, 'name' => $log->actor->display_name] : null,
                /** Lo que hace falta para leer la acción, con {from, to} cuando algo cambió. */
                'metadata' => $log->metadata,
                'created_at' => $log->created_at?->toIso8601String(),
            ])->values(),
            'meta' => ['total' => $page->total(), 'current_page' => $page->currentPage(), 'last_page' => $page->lastPage()],
        ]);
    }
}
