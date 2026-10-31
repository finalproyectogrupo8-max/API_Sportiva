<?php

namespace App\Http\Requests\Club;

use App\Models\Club\Player;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Nombre, apellido, documento o ficha.
            'search' => ['sometimes', 'string', 'max:100'],
            // Jugadores con una asignación activa en esa categoría.
            'category_id' => ['sometimes', 'integer'],
            'status' => ['sometimes', 'string', Rule::in(Player::STATUSES)],
            'sort' => ['sometimes', 'string', 'in:last_name,-last_name,first_name,-first_name,birth_date,-birth_date,created_at,-created_at'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
