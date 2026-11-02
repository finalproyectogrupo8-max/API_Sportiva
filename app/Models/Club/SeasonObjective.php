<?php

namespace App\Models\Club;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Objetivo de la temporada (TIZ-20): del club (sin categoría) o de una categoría.
 *
 * @property int $id
 * @property int $season_id
 * @property int|null $category_id
 * @property string $title
 * @property string|null $description
 * @property string|null $target_value
 * @property string|null $current_value
 * @property string|null $unit
 * @property string $status pending | in_progress | achieved | missed
 * @property string|null $due_date
 */
#[Fillable(['season_id', 'category_id', 'title', 'description', 'target_value', 'current_value', 'unit', 'status', 'due_date'])]
class SeasonObjective extends Model
{
    use SoftDeletes;

    public const STATUSES = ['pending', 'in_progress', 'achieved', 'missed'];

    protected $connection = 'tenant';

    protected function casts(): array
    {
        return ['due_date' => 'date:Y-m-d'];
    }

    /** @return BelongsTo<Season, $this> */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
