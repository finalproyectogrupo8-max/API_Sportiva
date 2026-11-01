<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Copiar una competencia a otra temporada: trae los requisitos, no las categorías. */
class CopyCompetitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'season_id' => ['required', 'integer', Rule::exists('tenant.seasons', 'id')],
            'name' => ['sometimes', 'string', 'max:150'],
            'registration_deadline' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
