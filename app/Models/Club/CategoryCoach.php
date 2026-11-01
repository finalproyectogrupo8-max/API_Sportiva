<?php

namespace App\Models\Club;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un profe a cargo de una categoría. Vigente si no tiene end_date o si todavía no llegó. Define el
 * alcance "sus categorías" de los permisos (ClubAccess::ownCategoryIds).
 *
 * @property int $id
 * @property int $category_id
 * @property int $coach_id
 * @property string $assignment_type
 * @property CarbonImmutable|null $start_date
 * @property CarbonImmutable|null $end_date
 */
#[Fillable(['category_id', 'coach_id', 'assignment_type', 'start_date', 'end_date'])]
class CategoryCoach extends Model
{
    public const TYPES = ['head_coach', 'assistant', 'physical_trainer'];

    public $timestamps = false;

    protected $connection = 'tenant';

    protected function casts(): array
    {
        return ['start_date' => 'immutable_date', 'end_date' => 'immutable_date'];
    }

    /** @param  Builder<self>  $query */
    public function scopeCurrent(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query->whereNull('end_date')->orWhere('end_date', '>=', today()->toDateString()));
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<Coach, $this> */
    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }
}
