<?php

namespace App\Http\Requests\Club;

use App\Models\Club\Player;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'birth_date' => ['sometimes', 'required', 'date', 'before:today', 'after:1900-01-01'],
            'document_number' => ['sometimes', 'nullable', 'string', 'max:30'],
            'registration_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'preferred_position' => ['sometimes', 'nullable', 'string', 'max:50'],
            'dominant_side' => ['sometimes', 'nullable', 'string', 'in:left,right,both'],
            'status' => ['sometimes', 'required', 'string', Rule::in(Player::STATUSES)],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'birth_date.before' => 'La fecha de nacimiento tiene que ser anterior a hoy.',
        ];
    }
}
