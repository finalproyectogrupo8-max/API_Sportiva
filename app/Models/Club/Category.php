<?php

namespace App\Models\Club;

use App\Club\Access\ClubAccess;
use Carbon\CarbonImmutable;
use Database\Factories\Club\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Categoría de un deporte en una temporada (Sub-15 de fútbol 2027).
 *
 * @property int $id
 * @property int $sport_id
 * @property int $season_id
 * @property string $name
 * @property int|null $min_age
 * @property int|null $max_age
 * @property bool $is_active
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
#[Fillable(['sport_id', 'season_id', 'name', 'min_age', 'max_age', 'is_active'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $connection = 'tenant';

    protected function casts(): array
    {
        return [
            'min_age' => 'integer',
            'max_age' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Con categories.view en alcance sport u own: solo las categorías alcanzables
     * (de sus deportes asignados o con asignación vigente como profe). Con alcance all no filtra.
     *
     * @param  Builder<self>  $query
     */
    public function scopeVisibleTo(Builder $query, ClubAccess $access): void
    {
        $scopedCategoryIds = $access->scopedCategoryIds('categories.view');

        if ($scopedCategoryIds !== null) {
            $query->whereIn('id', $scopedCategoryIds ?: [0]);
        }
    }

    /** @return BelongsToMany<Competition, $this> */
    public function competitions(): BelongsToMany
    {
        return $this->belongsToMany(Competition::class, 'category_competitions');
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }
}
