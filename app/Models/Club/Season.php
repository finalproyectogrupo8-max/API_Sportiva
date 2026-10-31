<?php

namespace App\Models\Club;

use Carbon\CarbonImmutable;
use Database\Factories\Club\SeasonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Temporada del club. `status`: planned, active o closed.
 *
 * @property int $id
 * @property string $name
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable $end_date
 * @property string $status
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
#[Fillable(['name', 'start_date', 'end_date', 'status'])]
class Season extends Model
{
    /** @use HasFactory<SeasonFactory> */
    use HasFactory;

    protected $connection = 'tenant';

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }
}
