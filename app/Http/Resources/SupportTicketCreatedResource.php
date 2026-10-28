<?php

namespace App\Http\Resources;

use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SupportTicket */
class SupportTicketCreatedResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => is_array($this->resource) ? ($this->resource['id'] ?? 0) : $this->id,
        ];
    }
}

