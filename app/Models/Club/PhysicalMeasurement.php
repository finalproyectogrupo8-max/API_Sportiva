<?php

namespace App\Models\Club;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Una prueba de una evaluación física: `metric_key` estable (`sprint_20m_sec`) y valor numérico o de texto.
 *
 * @property int $id
 * @property int $evaluation_id
 * @property string $metric_key
 * @property string $metric_name
 * @property string|null $numeric_value
 * @property string|null $unit
 * @property string|null $text_value
 * @property string|null $notes
 */
#[Fillable(['evaluation_id', 'metric_key', 'metric_name', 'numeric_value', 'unit', 'text_value', 'notes'])]
class PhysicalMeasurement extends Model
{
    protected $connection = 'tenant';

    public $timestamps = false;
}
