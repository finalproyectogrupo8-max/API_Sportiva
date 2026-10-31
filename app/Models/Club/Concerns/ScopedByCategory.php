<?php

namespace App\Models\Club\Concerns;

use App\Club\Access\ClubAccess;
use Illuminate\Database\Eloquent\Builder;

/** Registros de una categoría (category_id): con alcance "sus categorías", solo las del usuario. */
trait ScopedByCategory
{
    /** @param  Builder<static>  $query */
    public function scopeVisibleTo(Builder $query, ClubAccess $access, string $permission): void
    {
        if ($access->isOwnScope($permission)) {
            $query->whereIn($this->qualifyColumn('category_id'), $access->ownCategoryIds() ?: [0]);
        }
    }
}
