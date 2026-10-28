<?php

namespace App\Http\Resources\Admin;

use App\Enums\TicketStatus;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SupportTicket */
class SupportTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'club_name' => $this->club_name,
            'subject' => $this->subject,
            'message' => $this->message,
            'status' => $this->status instanceof TicketStatus ? $this->status->value : $this->status,
            'admin_notes' => $this->admin_notes,
            'handled_by' => $this->handled_by,
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
