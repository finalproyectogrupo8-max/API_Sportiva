<?php

namespace App\Models\Club;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Perfil local de un usuario global en este club. global_user_id apunta a sportiva_platform.users.id
 * (referencia lógica, no FK entre bases).
 *
 * @property int $id
 * @property int $global_user_id
 * @property string $display_name
 * @property string $status active | disabled
 */
#[Fillable(['global_user_id', 'display_name', 'status'])]
class ClubUser extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_DISABLED = 'disabled';

    protected $connection = 'tenant';

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'club_user_roles')
            ->withPivot('created_at');
    }

    public function coach(): HasOne
    {
        return $this->hasOne(Coach::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
