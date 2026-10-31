<?php

namespace App\Models\Club;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Archivo de un registro del club (TIZ-29). Privado: se descarga solo por la API, con permiso.
 *
 * @property int $id
 * @property string $documentable_type
 * @property int $documentable_id
 * @property string $kind
 * @property string $original_name
 * @property string $disk
 * @property string $path
 * @property string $mime
 * @property int $size
 * @property int|null $uploaded_by
 */
#[Fillable(['documentable_type', 'documentable_id', 'kind', 'original_name', 'disk', 'path', 'mime', 'size', 'uploaded_by'])]
class Document extends Model
{
    use SoftDeletes;

    /** Tipos de documento de un jugador. `photo` es la foto de la ficha; el resto son papeles. */
    public const PLAYER_KINDS = ['photo', 'medical', 'id_document', 'federation_card', 'authorization', 'other'];

    protected $connection = 'tenant';

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    /** @return MorphTo<Model, $this> */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }
}
