<?php

namespace App\Http\Resources\Club;

use App\Club\Callups\CallupRow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CallupRow */
class CallupRowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'player_id' => $this->player_id,
            'player_name' => $this->player_name,
            'shirt_number' => $this->shirt_number,
            /** `pending`, `going` o `not_going`. */
            'response' => $this->response,
            'response_note' => $this->response_note,
            'responded_at' => $this->responded_at,
            /** Link personal para confirmar (página del front). */
            'personal_url' => $this->personal_url,
            /** WhatsApp al contacto principal con el mensaje armado; null si no tiene teléfono. */
            'whatsapp_url' => $this->whatsapp_url,
            /** El contacto principal tiene email: recibe el aviso por mail (TIZ-37). Si no, hay que avisarle por otro medio. */
            'has_email' => $this->has_email,
            /** Último aviso por mail enviado (ISO 8601); null si nunca se le mandó. */
            'notified_at' => $this->notified_at,
        ];
    }
}
