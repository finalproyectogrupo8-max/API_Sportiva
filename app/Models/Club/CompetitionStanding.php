<?php

namespace App\Models\Club;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Fila de la tabla de posiciones de una competencia y categoría (TIZ-20). Se carga a mano.
 *
 * @property int $id
 * @property int $competition_id
 * @property int $category_id
 * @property int $position
 * @property string $team_name
 * @property bool $is_own
 * @property int $played
 * @property int $won
 * @property int $drawn
 * @property int $lost
 * @property int $goals_for
 * @property int $goals_against
 * @property int $points
 */
#[Fillable(['competition_id', 'category_id', 'position', 'team_name', 'is_own', 'played', 'won', 'drawn', 'lost', 'goals_for', 'goals_against', 'points'])]
class CompetitionStanding extends Model
{
    public const COUNTERS = ['played', 'won', 'drawn', 'lost', 'goals_for', 'goals_against', 'points'];

    protected $connection = 'tenant';

    protected function casts(): array
    {
        return ['is_own' => 'boolean', ...array_fill_keys([...self::COUNTERS, 'position'], 'integer')];
    }
}
