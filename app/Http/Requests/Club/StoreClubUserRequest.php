<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

/** Sumar a alguien al equipo del club por email, con uno o más roles (TIZ-34). */
class StoreClubUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            /** Obligatorio si la persona todavía no tiene usuario en Sportiva. */
            'name' => ['nullable', 'string', 'max:255'],
            'password' => ['sometimes', 'nullable', 'string', 'min:12', 'max:255'],
            'roles' => ['required', 'array', 'min:1', 'max:10'],
            'roles.*' => ['string', 'distinct', 'max:60'],
            /** Deportes del coordinador de deporte. */
            'sport_ids' => ['sometimes', 'array'],
            'sport_ids.*' => ['integer', 'distinct'],
        ];
    }
}
