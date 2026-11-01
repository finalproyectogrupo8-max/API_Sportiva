<?php

namespace App\Models\Club;

use Carbon\CarbonImmutable;
use Database\Factories\Club\SportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Deporte del club (fútbol, básquet, vóley).
 *
 * @property int $id
 * @property string $name
 * @property bool $is_active
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
#[Fillable(['name', 'is_active'])]
class Sport extends Model
{
    /** @use HasFactory<SportFactory> */
    use HasFactory;

    protected $connection = 'tenant';

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function clubUsers(): BelongsToMany
    {
        return $this->belongsToMany(ClubUser::class, 'club_user_sports')
            ->withTimestamps();
    }
}
