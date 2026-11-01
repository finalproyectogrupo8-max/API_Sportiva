<?php

namespace App\Http\Controllers\Club;

use App\Club\Documents\DocumentStore;
use App\Http\Controllers\Club\Concerns\ChecksClubScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\StoreDocumentRequest;
use App\Http\Resources\Club\DocumentResource;
use App\Models\Club\Document;
use App\Models\Club\Player;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Documentos de los jugadores (TIZ-29). El archivo nunca tiene URL pública: se descarga por la API,
 * que valida el permiso y el alcance, y el front lo reenvía (BFF). Cada acceso a un papel queda en
 * la auditoría; la foto de la ficha no, porque se muestra en todos lados.
 */
class DocumentController extends Controller
{
    use ChecksClubScope;

    public function __construct(private DocumentStore $store) {}

    public function index(Player $player): AnonymousResourceCollection
    {
        $this->authorizeClub('player_documents.view');
        $this->ensurePlayerVisible($player, 'player_documents.view');

        return DocumentResource::collection($player->documents()->latest()->orderByDesc('id')->get());
    }

    /** La foto nueva reemplaza a la anterior. */
    public function store(StoreDocumentRequest $request, Player $player): JsonResponse
    {
        $this->authorizeClub('player_documents.upload');
        $this->ensurePlayerVisible($player, 'player_documents.upload');

        $kind = $request->validated('kind');
        $tenant = $request->attributes->get('tenant');

        $document = DB::connection('tenant')->transaction(function () use ($request, $player, $kind, $tenant) {
            if ($kind === 'photo') {
                $player->documents()->where('kind', 'photo')->get()->each(fn (Document $old) => $this->store->delete($old));
            }

            return $this->store->store($request->file('file'), $player, $kind, $tenant, $request->user()->currentClubUser()?->id);
        });

        return DocumentResource::make($document)->response()->setStatusCode(201);
    }

    /**
     * El archivo, para mostrarlo o bajarlo. La foto la ve quien ve al jugador (`players.view`); los
     * papeles piden `player_documents.view`.
     */
    public function download(Request $request, Document $document, AuditLogger $auditLogger): StreamedResponse
    {
        $permission = $document->kind === 'photo' ? 'players.view' : 'player_documents.view';
        $this->authorizeClub($permission);
        $this->ensureOwnerVisible($document, $permission);

        if ($document->kind !== 'photo') {
            $auditLogger->recordDocument($document, $request->attributes->get('tenant'), 'downloaded');
        }

        return Storage::disk($document->disk)->response($document->path, $document->original_name, [
            'Content-Type' => $document->mime,
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function destroy(Request $request, Document $document, AuditLogger $auditLogger): Response
    {
        $this->authorizeClub('player_documents.delete');
        $this->ensureOwnerVisible($document, 'player_documents.view');

        $this->store->delete($document);
        $auditLogger->recordDocument($document, $request->attributes->get('tenant'), 'deleted');

        return response()->noContent();
    }

    private function ensureOwnerVisible(Document $document, string $permission): void
    {
        $owner = $document->documentable;
        abort_unless($owner instanceof Player, 404);
        $this->ensurePlayerVisible($owner, $permission);
    }
}
