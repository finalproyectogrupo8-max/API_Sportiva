<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

/** Alta y edición de una sede del club (TIZ-39). En PATCH, todo es opcional. */
class StoreVenueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $req = $this->isMethod('POST') ? ['required'] : ['sometimes', 'required'];

        return [
            'name' => [...$req, 'string', 'max:120'],
            'address' => [...$req, 'string', 'max:255'],
            // Del buscador de direcciones; se mandan juntas o ninguna.
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Poné el nombre de la sede.',
            'address.required' => 'Poné la dirección.',
        ];
    }
}
