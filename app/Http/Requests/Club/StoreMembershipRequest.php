<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Sumar un jugador al plantel de una categoría. */
class StoreMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'player_id' => ['required', 'integer', Rule::exists('tenant.players', 'id')->whereNull('deleted_at')],
            'start_date' => ['sometimes', 'date'],
            'shirt_number' => ['nullable', 'integer', 'min:0', 'max:999'],
            // Si ya juega en otra categoría del mismo deporte, cierra esa asignación (pase de categoría).
            'close_previous' => ['sometimes', 'boolean'],
        ];
    }
}
