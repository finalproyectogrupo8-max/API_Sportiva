<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Alta y edición de entrenamientos. En PATCH todo es opcional. */
class StoreTrainingRequest extends FormRequest
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
            'category_id' => [...$req, 'integer', Rule::exists('tenant.categories', 'id')],
            'coach_id' => [...$opt, 'nullable', 'integer', Rule::exists('tenant.coaches', 'id')->whereNull('deleted_at')],
            'starts_at' => [...$req, 'date'],
            'ends_at' => [...$req, 'date', 'after:starts_at'],
            'location' => [...$opt, 'nullable', 'string', 'max:150'],
            'objectives' => [...$opt, 'nullable', 'string', 'max:2000'],
            'notes' => [...$opt, 'nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return ['ends_at.after' => 'El entrenamiento tiene que terminar después de empezar.'];
    }
}
