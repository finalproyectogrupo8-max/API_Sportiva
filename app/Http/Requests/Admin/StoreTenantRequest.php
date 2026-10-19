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
        ];
    }
}
