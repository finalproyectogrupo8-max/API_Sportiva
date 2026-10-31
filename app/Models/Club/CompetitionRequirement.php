<?php

namespace App\Models\Club;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Papel que pide una competencia para habilitar a un jugador: ficha, apto médico, DNI...
 *
 * @property int $id
 * @property int $competition_id
 * @property string $name
 * @property int|null $validity_months
 * @property bool $requires_file
 * @property bool $minors_only
 * @property bool $is_required
 * @property int $sort
 */
#[Fillable(['competition_id', 'name', 'validity_months', 'requires_file', 'minors_only', 'is_required', 'sort'])]
class CompetitionRequirement extends Model
{
    protected $connection = 'tenant';

    protected function casts(): array
    {
        return [
            'validity_months' => 'integer',
            'requires_file' => 'boolean',
            'minors_only' => 'boolean',
            'is_required' => 'boolean',
            'sort' => 'integer',
        ];
    }

    /** @return BelongsTo<Competition, $this> */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }
}
