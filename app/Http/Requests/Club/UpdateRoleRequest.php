<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:80'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            // Reemplaza la lista completa.
            'permissions' => ['sometimes', 'required', 'array', 'min:1'],
            'permissions.*' => ['required', 'array:name,scope'],
            'permissions.*.name' => ['required', 'string', 'max:120', 'distinct'],
            'permissions.*.scope' => ['required', 'string', 'in:all,own'],
        ];
    }

    public function messages(): array
    {
        return [
            'permissions.required' => 'Elegí al menos un permiso.',
            'permissions.min' => 'Elegí al menos un permiso.',
        ];
    }
}
