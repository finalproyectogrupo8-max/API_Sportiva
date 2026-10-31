<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta y edición de competencias con sus requisitos y categorías. En PATCH, `requirements` y
 * `category_ids` reemplazan la lista completa si vienen; si no vienen, no se tocan.
 */
class StoreCompetitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $req = $this->isMethod('POST') ? ['required'] : ['sometimes', 'required'];
        $opt = $this->isMethod('POST') ? [] : ['sometimes'];

        return [
            'sport_id' => [...$req, 'integer', Rule::exists('tenant.sports', 'id')],
            'season_id' => [...$req, 'integer', Rule::exists('tenant.seasons', 'id')],
            'name' => [...$req, 'string', 'max:150'],
            'organizer' => [...$opt, 'nullable', 'string', 'max:150'],
            'registration_deadline' => [...$opt, 'nullable', 'date'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'distinct', Rule::exists('tenant.categories', 'id')],
            'requirements' => ['sometimes', 'array', 'max:30'],
            // Con id se edita ese requisito; sin id se crea. Los que no vienen se borran.
            'requirements.*.id' => ['sometimes', 'integer'],
            'requirements.*.name' => ['required', 'string', 'max:120'],
            'requirements.*.validity_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'requirements.*.requires_file' => ['sometimes', 'boolean'],
            'requirements.*.minors_only' => ['sometimes', 'boolean'],
            'requirements.*.is_required' => ['sometimes', 'boolean'],
        ];
    }
}
