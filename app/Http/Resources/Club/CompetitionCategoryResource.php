<?php

namespace App\Http\Resources\Club;

use App\Models\Club\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Categoría que juega una competencia.
 *
 * @mixin Category
 */
class CompetitionCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'name' => $this->name];
    }
}
