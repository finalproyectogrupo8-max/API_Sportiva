<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // club_ (5) + slug (máx. 50) = 55 caracteres, bajo el límite de 64 de MySQL
            'slug' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', 'unique:tenants,slug'],
            // Los fija el servidor
            'database_name' => ['prohibited'],
            'status' => ['prohibited'],
            // Opcional: el administrador del club, que se crea y se asocia junto con el club.
            'admin' => ['sometimes', 'array:name,email,password'],
            'admin.name' => ['required_with:admin', 'string', 'max:255'],
            'admin.email' => ['required_with:admin', 'email', 'max:255', 'unique:users,email'],
            'admin.password' => ['required_with:admin', 'string', 'min:12', 'max:255'],
        ];
    }
}
