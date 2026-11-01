<?php

namespace App\Models\Club;

use App\Models\Club\Concerns\ScopedByCategory;
use Carbon\CarbonImmutable;
use Database\Factories\Club\TrainingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Entrenamiento de una categoría.
 *
 * @property int $id
 * @property int $category_id
 * @property int|null $coach_id
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable $ends_at
 * @property string|null $location
 * @property string|null $objectives
 * @property string|null $notes
 */
#[Fillable(['category_id', 'coach_id', 'starts_at', 'ends_at', 'location', 'objectives', 'notes'])]
class Training extends Model
{
    /** @use HasFactory<TrainingFactory> */
    use HasFactory, ScopedByCategory, SoftDeletes;

    protected $connection = 'tenant';

    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime'];
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

    /** @return HasMany<PlayerAttendance, $this> */
    public function attendance(): HasMany
    {
        return $this->hasMany(PlayerAttendance::class);
    }
}
