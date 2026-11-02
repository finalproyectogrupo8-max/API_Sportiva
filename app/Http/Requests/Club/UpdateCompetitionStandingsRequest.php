<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Reemplaza la tabla de posiciones de una categoría en una competencia (TIZ-20). Las filas van en orden. */
class UpdateCompetitionStandingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists('tenant.categories', 'id')],
            /** Del primero al último. Una lista vacía borra la tabla. */
            'rows' => ['present', 'array', 'max:40'],
            'rows.*.team_name' => ['required', 'string', 'max:120', 'distinct:ignore_case'],
            'rows.*.is_own' => ['sometimes', 'boolean'],
            'rows.*.played' => ['sometimes', 'integer', 'min:0', 'max:999'],
            'rows.*.won' => ['sometimes', 'integer', 'min:0', 'max:999'],
            'rows.*.drawn' => ['sometimes', 'integer', 'min:0', 'max:999'],
            'rows.*.lost' => ['sometimes', 'integer', 'min:0', 'max:999'],
            'rows.*.goals_for' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'rows.*.goals_against' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'rows.*.points' => ['sometimes', 'integer', 'min:-999', 'max:9999'],
        ];
    }

    public function attributes(): array
    {
        return ['rows.*.team_name' => 'equipo'];
    }
}
