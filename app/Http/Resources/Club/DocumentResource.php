<?php

namespace App\Http\Resources\Club;

use App\Models\Club\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Document */
class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            /** `photo`, `medical`, `id_document`, `federation_card`, `authorization` u `other`. */
            'kind' => $this->kind,
            'original_name' => $this->original_name,
            'mime' => $this->mime,
            /** Tamaño en bytes. */
            'size' => $this->size,
            'uploaded_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
