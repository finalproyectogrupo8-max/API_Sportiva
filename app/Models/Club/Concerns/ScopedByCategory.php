<?php

namespace App\Models\Club\Concerns;

use App\Club\Access\ClubAccess;
use Illuminate\Database\Eloquent\Builder;

/** Registros de una categoría (category_id): con alcance reducido (sport u own), solo las del usuario. */
trait ScopedByCategory
{
    /** @param  Builder<static>  $query */
    public function scopeVisibleTo(Builder $query, ClubAccess $access, string $permission): void
    {
        $scopedCategoryIds = $access->scopedCategoryIds($permission);

        if ($scopedCategoryIds !== null) {
            $query->whereIn($this->qualifyColumn('category_id'), $scopedCategoryIds ?: [0]);
        }
    }
}
