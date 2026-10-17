<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes', 'required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/',
                Rule::unique('tenants', 'slug')->ignore($tenant),
            ],
            'database_name' => [
                'sometimes', 'required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/',
                Rule::unique('tenants', 'database_name')->ignore($tenant),
            ],
            'status' => ['sometimes', 'required', 'string', 'in:provisioning,active,suspended,failed'],
        ];
    }
}
