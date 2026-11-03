<?php

namespace App\Http\Controllers\Club;

use App\Club\Announcements\AnnouncementScope;
use App\Club\Announcements\AudienceResolver;
use App\Club\Audit\ClubAudit;
use App\Http\Controllers\Club\Concerns\ChecksClubScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\IndexAnnouncementRecipientRequest;
use App\Http\Requests\Club\IndexAnnouncementRequest;
use App\Http\Requests\Club\PreviewAnnouncementAudienceRequest;
use App\Http\Requests\Club\StoreAnnouncementRequest;
use App\Http\Requests\Club\UpdateAnnouncementRequest;
use App\Http\Resources\Club\AnnouncementRecipientResource;
use App\Http\Resources\Club\AnnouncementResource;
use App\Jobs\Club\DispatchAnnouncement;
use App\Models\Club\Announcement;
use App\Models\Club\AnnouncementRecipient;
use App\Support\ApiError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Comunicados por mail del club (TIZ-24): borradores, vista previa de la audiencia, envío
 * deduplicado por dirección e historial por destinatario. Con alcance limitado, cada uno ve y
 * envía solo los suyos, y solo a sus categorías.
 */
class AnnouncementController extends Controller
{
    use ChecksClubScope;

    public function __construct(private AudienceResolver $audience) {}

    public function index(IndexAnnouncementRequest $request): AnonymousResourceCollection
    {
        $this->authorizeClub('announcements.view');
        $filters = $request->validated();

        $announcements = $this->visible('announcements.view')
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate($filters['per_page'] ?? 20);

        return AnnouncementResource::collection($announcements);
    }

    public function store(StoreAnnouncementRequest $request): JsonResponse
    {
        $this->authorizeClub('announcements.send');
        $data = $request->validated();
        AnnouncementScope::check($this->access(), $data['audience']);

        $announcement = Announcement::create([...$data, 'status' => Announcement::DRAFT, 'created_by' => $this->clubUserId()]);

        return AnnouncementResource::make($announcement->refresh())->response()->setStatusCode(201);
    }

    public function show(Announcement $announcement): AnnouncementResource
    {
        $this->authorizeClub('announcements.view');
        $this->ensureVisible($announcement, 'announcements.view');

        return AnnouncementResource::make($announcement);
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): AnnouncementResource|JsonResponse
    {
        $this->authorizeClub('announcements.send');
        $this->ensureVisible($announcement, 'announcements.send');
        if ($announcement->status !== Announcement::DRAFT) {
            return ApiError::conflict('announcement_not_draft');
        }

        $data = $request->validated();
        AnnouncementScope::check($this->access(), $data['audience'] ?? $announcement->audience);
        $announcement->update($data);

        return AnnouncementResource::make($announcement->refresh());
    }

    public function destroy(Announcement $announcement): Response|JsonResponse
    {
        $this->authorizeClub('announcements.send');
        $this->ensureVisible($announcement, 'announcements.send');
        if ($announcement->status !== Announcement::DRAFT) {
            return ApiError::conflict('announcement_not_draft');
        }

        $announcement->delete();

        return response()->noContent();
    }

    /** Cuántas direcciones recibirían el mail, cuántos quedan afuera sin email y una muestra. No envía nada. */
    public function previewAudience(PreviewAnnouncementAudienceRequest $request): JsonResponse
    {
        $this->authorizeClub('announcements.send');
        $audience = $request->validated('audience');
        AnnouncementScope::check($this->access(), $audience);

        $resolved = $this->audience->resolve($audience);

        return response()->json(['data' => [
            /** Direcciones distintas que recibirían el mail. */
            'recipients_count' => count($resolved['recipients']),
            /** Personas incluidas que no tienen email. */
            'without_email_count' => count($resolved['without_email']),
            'without_email' => array_slice($resolved['without_email'], 0, 10),
            /** Hasta 10 nombres de destinatarios. */
            'sample' => AudienceResolver::sample($resolved['recipients']),
        ]]);
    }

    /** Congela los destinatarios y encola un mail por dirección. Responde 202: el envío sigue en la cola. */
    public function send(Request $request, Announcement $announcement): JsonResponse
    {
        $this->authorizeClub('announcements.send');
        $this->ensureVisible($announcement, 'announcements.send');
        if ($announcement->status !== Announcement::DRAFT) {
            return ApiError::conflict('announcement_not_draft');
        }
        AnnouncementScope::check($this->access(), $announcement->audience);

        $recipients = $this->audience->resolve($announcement->audience)['recipients'];
        if ($recipients === []) {
            throw ValidationException::withMessages(['audience' => 'Ninguno de los destinatarios tiene email.']);
        }

        $tenant = $request->attributes->get('tenant');
        $started = DB::connection('tenant')->transaction(function () use ($announcement, $recipients, $tenant) {
            // Bloquea la fila: si llegan dos "Enviar" a la vez, el segundo ve que ya no es borrador.
            $locked = Announcement::query()->lockForUpdate()->find($announcement->id);
            if (! $locked || $locked->status !== Announcement::DRAFT) {
                return false;
            }

            foreach ($recipients as $recipient) {
                AnnouncementRecipient::create([...$recipient, 'announcement_id' => $announcement->id, 'status' => AnnouncementRecipient::PENDING]);
            }

            $announcement->update([
                'status' => Announcement::SENDING,
                'sent_by' => $this->clubUserId(),
                'sent_at' => now(),
                'recipients_count' => count($recipients),
            ]);

            DispatchAnnouncement::dispatch($tenant->id, $announcement->id)->afterCommit();

            return true;
        });

        if (! $started) {
            return ApiError::conflict('announcement_not_draft');
        }
        app(ClubAudit::class)->record('announcement.sent', 'announcement', $announcement->id, ['subject' => $announcement->subject, 'recipients' => count($recipients)]);

        return AnnouncementResource::make($announcement->refresh())->response()->setStatusCode(202);
    }

    public function recipients(IndexAnnouncementRecipientRequest $request, Announcement $announcement): AnonymousResourceCollection
    {
        $this->authorizeClub('announcements.view');
        $this->ensureVisible($announcement, 'announcements.view');
        $filters = $request->validated();

        $recipients = $announcement->recipients()
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('name')
            ->paginate($filters['per_page'] ?? 20);

        return AnnouncementRecipientResource::collection($recipients);
    }

    /** Con alcance limitado, solo los comunicados que creó el mismo usuario. */
    private function visible(string $permission)
    {
        return Announcement::query()->when(
            $this->access()->scopedCategoryIds($permission) !== null,
            fn ($q) => $q->where('created_by', $this->clubUserId()),
        );
    }

    private function ensureVisible(Announcement $announcement, string $permission): void
    {
        abort_unless($this->visible($permission)->whereKey($announcement->id)->exists(), 404);
    }

    private function clubUserId(): int
    {
        return $this->access()->clubUser->id;
    }
}
