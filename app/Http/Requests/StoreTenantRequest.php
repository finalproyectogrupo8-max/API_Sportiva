<?php

namespace App\Http\Requests;

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
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/', 'unique:tenants,slug'],
            'database_name' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/', 'unique:tenants,database_name'],
            'status' => ['sometimes', 'string', 'in:provisioning,active,suspended,failed'],
        ];
    }
}
