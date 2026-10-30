<?php

namespace App\Models\Club;

use App\Club\Access\ClubAccess;
use Database\Factories\Club\CoachFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['club_user_id', 'first_name', 'last_name', 'phone', 'email', 'status'])]
class Coach extends Model
{
    /** @use HasFactory<CoachFactory> */
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';

    /**
     * Con coaches.view en alcance "sus categorías": su propia ficha y los profes que comparten
     * alguna de sus categorías (asignación vigente). Con alcance all no filtra.
     *
     * @param  Builder<self>  $query
     */
    public function scopeVisibleTo(Builder $query, ClubAccess $access): void
    {
        if (! $access->isOwnScope('coaches.view')) {
            return;
        }

        $clubUserId = $access->clubUser?->id;
        $categoryIds = $access->ownCategoryIds();

        $query->where(fn (Builder $query) => $query
            ->when($clubUserId, fn (Builder $query) => $query->where('club_user_id', $clubUserId))
            ->orWhereIn('id', fn ($sub) => $sub->select('coach_id')
                ->from('category_coaches')
                ->whereIn('category_id', $categoryIds ?: [0])
                ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', today()->toDateString()))));
    }
}
