<?php

namespace App\Http\Resources\Club;

use App\Models\Club\SeasonObjective;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SeasonObjective */
class SeasonObjectiveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $target = $this->target_value !== null ? (float) $this->target_value : null;
        $current = $this->current_value !== null ? (float) $this->current_value : null;

        return [
            'id' => $this->id,
            'season_id' => $this->season_id,
            'category_id' => $this->category_id,
            'category_name' => $this->whenLoaded('category', fn () => $this->category?->name),
            'title' => $this->title,
            'description' => $this->description,
            'target_value' => $target,
            'current_value' => $current,
            'unit' => $this->unit,
            /** Avance de 0 a 100; null si el objetivo no tiene meta numérica. */
            'progress' => $target ? min(100, (int) round(($current ?? 0) / $target * 100)) : null,
            /** `pending`, `in_progress`, `achieved` o `missed`. */
            'status' => $this->status,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
