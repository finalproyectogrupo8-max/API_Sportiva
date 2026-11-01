<?php

namespace App\Http\Resources\Club;

use App\Club\Callups\CallupSheet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CallupSheet */
class CallupSheetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'match_id' => $this->match_id,
            'published_at' => $this->published_at,
            /** Cambió la fecha, la hora o el lugar después de publicar: hay que volver a avisar. */
            'updated_after_publish' => $this->updated_after_publish,
            /** Página pública de la convocatoria; null hasta publicar. */
            'public_url' => $this->public_url,
            /** Mensaje para el grupo de WhatsApp; null hasta publicar. */
            'group_message' => $this->group_message,
            'going' => $this->going,
            'not_going' => $this->not_going,
            'pending' => $this->pending,
            'callups' => CallupRowResource::collection($this->callups),
            /** El plantel de la categoría, con quién está habilitado y quién está convocado. */
            'squad' => SquadOptionResource::collection($this->squad),
        ];
    }
}
