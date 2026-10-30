<?php

namespace App\Models\Club;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Permiso del catálogo (config/club-permissions.php). name = "<recurso>.<acción>".
 *
 * @property int $id
 * @property string $name
 * @property string $module
 * @property string|null $description
 */
#[Fillable(['name', 'module', 'description'])]
class Permission extends Model
{
    public const SCOPE_ALL = 'all';

    public const SCOPE_OWN = 'own';

    protected $connection = 'tenant';

    public $timestamps = false;

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions')
            ->withPivot('scope');
    }
}
