<?php

namespace App\Http\Resources\Club;

use App\Models\Club\Announcement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Announcement */
class AnnouncementResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            /** Markdown. */
            'body' => $this->body,
            /** `categories`, `players`, `role_slugs` y `club_user_ids`, como se mandaron. */
            'audience' => $this->audience,
            /** `draft`, `sending` o `sent`. */
            'status' => $this->status,
            'created_by' => $this->created_by,
            'sent_by' => $this->sent_by,
            'sent_at' => $this->sent_at?->toIso8601String(),
            /** Direcciones distintas a las que se envió. */
            'recipients_count' => $this->recipients_count,
            'sent_count' => $this->sent_count,
            'failed_count' => $this->failed_count,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
