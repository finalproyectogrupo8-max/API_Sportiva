<?php

namespace App\Http\Requests\Admin;

use App\Enums\TenantStatus;
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
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'status' => ['sometimes', 'required', Rule::in([
                TenantStatus::Active->value,
                TenantStatus::Suspended->value,
            ])],
            // Inmutables: rechazar explícitamente en vez de ignorar
            'slug' => ['prohibited'],
            'database_name' => ['prohibited'],
        ];
    }
}
