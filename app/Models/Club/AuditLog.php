<?php

namespace App\Models\Club;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de actividad del club (FRO-30): quién hizo qué y cuándo, con el antes y el después en
 * `metadata`. Solo se escribe; no se edita ni se borra.
 *
 * @property int $id
 * @property int|null $club_user_id
 * @property string $action
 * @property string $entity_type
 * @property int|null $entity_id
 * @property array<string, mixed>|null $metadata
 * @property string|null $ip_address
 */
#[Fillable(['club_user_id', 'action', 'entity_type', 'entity_id', 'metadata', 'ip_address'])]
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $connection = 'tenant';

    protected function casts(): array
    {
        return ['metadata' => 'array', 'created_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<ClubUser, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(ClubUser::class, 'club_user_id');
    }
}
