<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'status' => ['sometimes', 'required', 'string', 'in:provisioning,active,suspended,failed'],
            // Inmutables: rechazar explícitamente en vez de ignorar
            'slug' => ['prohibited'],
            'database_name' => ['prohibited'],
        ];
    }
}
