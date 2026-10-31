<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $short_name
 * @property string|null $city
 * @property int|null $founded_year
 * @property string|null $primary_color
 * @property string $database_name
 * @property string $status
 */
class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'slug',
        'name',
        'short_name',
        'city',
        'founded_year',
        'primary_color',
        'database_name',
        'status',
    ];

    /** Campos del perfil que se editan desde el panel o desde el club. */
    public const PROFILE_FIELDS = ['short_name', 'city', 'founded_year', 'primary_color'];

    protected function casts(): array
    {
        return ['founded_year' => 'integer'];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(TenantMembership::class);
    }
}
