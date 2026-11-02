<?php

namespace App\Http\Resources\Club;

use App\Models\Club\PhysicalEvaluation;
use App\Models\Club\PhysicalMeasurement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PhysicalEvaluation */
class PhysicalEvaluationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'player_id' => $this->player_id,
            'player_name' => $this->whenLoaded('player', fn () => trim($this->player->first_name.' '.$this->player->last_name)),
            'category_id' => $this->category_id,
            'category_name' => $this->whenLoaded('category', fn () => $this->category?->name),
            'evaluator_coach_id' => $this->evaluator_coach_id,
            'evaluator_name' => $this->whenLoaded('evaluator', fn () => $this->evaluator ? trim($this->evaluator->first_name.' '.$this->evaluator->last_name) : null),
            'evaluated_at' => $this->evaluated_at->toIso8601String(),
            'summary' => $this->summary,
            'recommendations' => $this->recommendations,
            /** Una fila por prueba: `numeric_value` o `text_value`. */
            'measurements' => $this->whenLoaded('measurements', fn () => $this->measurements->map(fn (PhysicalMeasurement $m) => [
                'metric_key' => $m->metric_key,
                'metric_name' => $m->metric_name,
                'numeric_value' => $m->numeric_value !== null ? (float) $m->numeric_value : null,
                'unit' => $m->unit,
                'text_value' => $m->text_value,
                'notes' => $m->notes,
            ])->values()),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
