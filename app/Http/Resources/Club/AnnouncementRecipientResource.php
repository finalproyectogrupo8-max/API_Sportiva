<?php

namespace App\Http\Resources\Club;

use App\Models\Club\AnnouncementRecipient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AnnouncementRecipient */
class AnnouncementRecipientResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            /** `pending`, `sent` o `failed`. */
            'status' => $this->status,
            /** Error del proveedor, si falló. */
            'error' => $this->error,
            'sent_at' => $this->sent_at?->toIso8601String(),
        ];
    }
}
