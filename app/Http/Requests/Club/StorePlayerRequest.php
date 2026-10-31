<?php

namespace App\Http\Requests\Club;

use App\Models\Club\Player;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            // Obligatoria: define los requisitos "solo menores" de la habilitación (TIZ-32).
            'birth_date' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'document_number' => ['nullable', 'string', 'max:30'],
            'registration_number' => ['nullable', 'string', 'max:50'],
            'preferred_position' => ['nullable', 'string', 'max:50'],
            'dominant_side' => ['nullable', 'string', 'in:left,right,both'],
            'status' => ['sometimes', 'string', Rule::in(Player::STATUSES)],
            // Nada médico: eso va en los requisitos y documentos.
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'birth_date.required' => 'La fecha de nacimiento es obligatoria.',
            'birth_date.before' => 'La fecha de nacimiento tiene que ser anterior a hoy.',
        ];
    }
}
