<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

/** Cambiar el número de camiseta de un jugador en la categoría. */
class UpdateMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shirt_number' => ['present', 'nullable', 'integer', 'min:0', 'max:999'],
        ];
    }
}
