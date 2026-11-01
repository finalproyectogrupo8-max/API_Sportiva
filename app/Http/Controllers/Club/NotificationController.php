<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Http\Resources\Club\NotificationResource;
use App\Models\Club\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    /**
     * Notificaciones propias del usuario en el club, paginadas.
     * Soporta filtro ?unread=1 para ver solo las pendientes de lectura.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $clubUser = $request->user()->currentClubUser();
        abort_unless($clubUser, 403);

        $query = Notification::query()
            ->where('club_user_id', $clubUser->id)
            ->when($request->boolean('unread'), fn ($q) => $q->whereNull('read_at'))
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);

        return NotificationResource::collection($query->paginate($perPage));
    }

    /**
     * Marca una notificación como leída.
     */
    public function markAsRead(Request $request, int $id): NotificationResource
    {
        $clubUser = $request->user()->currentClubUser();
        abort_unless($clubUser, 403);

        $notification = Notification::query()
            ->where('club_user_id', $clubUser->id)
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        return NotificationResource::make($notification);
    }

    /**
     * Marca todas las notificaciones pendientes del usuario en el club como leídas.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $clubUser = $request->user()->currentClubUser();
        abort_unless($clubUser, 403);

        $affected = Notification::query()
            ->where('club_user_id', $clubUser->id)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'Todas las notificaciones fueron marcadas como leídas.',
            'affected' => $affected,
        ]);
    }
}
