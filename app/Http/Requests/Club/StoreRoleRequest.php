<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

/** Rol custom. Que los permisos existan y que el actor pueda darlos lo valida ManageClubRoles. */
class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'array:name,scope'],
            'permissions.*.name' => ['required', 'string', 'max:120', 'distinct'],
            'permissions.*.scope' => ['required', 'string', 'in:all,sport,own'],
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
