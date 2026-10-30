<?php

namespace App\Models\Club;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Rol del club. Los estándar (is_system) salen de config/club-permissions.php y no se editan.
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property bool $is_system
 */
#[Fillable(['slug', 'name', 'description', 'is_system'])]
class Role extends Model
{
    protected $connection = 'tenant';

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withPivot('scope');
    }

    public function clubUsers(): BelongsToMany
    {
        return $this->belongsToMany(ClubUser::class, 'club_user_roles');
    }
}
