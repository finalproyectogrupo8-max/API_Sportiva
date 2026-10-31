<?php

namespace App\Models\Club;

use App\Club\Access\ClubAccess;
use Database\Factories\Club\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
     * Con categories.view en alcance "sus categorías": solo las categorías con asignación vigente
     * del usuario (el profe de fútbol no ve las de básquet). Con alcance all no filtra.
     *
     * @param  Builder<self>  $query
     */
    public function scopeVisibleTo(Builder $query, ClubAccess $access): void
    {
        if ($access->isOwnScope('categories.view')) {
            $query->whereIn('id', $access->ownCategoryIds() ?: [0]);
        }
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
