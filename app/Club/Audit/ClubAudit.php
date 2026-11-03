<?php

namespace App\Club\Audit;

use App\Models\Club\AuditLog;
use Illuminate\Http\Request;

/**
 * Escribe en el registro de actividad del club (FRO-30). Requiere la conexión "tenant" apuntando
 * al club. Un fallo al auditar nunca rompe la acción que se audita.
 */
class ClubAudit
{
    public function __construct(private Request $request) {}

    /** @param array<string, mixed> $metadata */
    public function record(string $action, string $entityType, ?int $entityId, array $metadata = []): void
    {
        try {
            AuditLog::query()->create([
                'club_user_id' => $this->request->user()?->currentClubUser()?->id,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'metadata' => $metadata ?: null,
                'ip_address' => $this->request->ip(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
