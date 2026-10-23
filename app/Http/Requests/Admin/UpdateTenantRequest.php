<?php

namespace App\Http\Requests\Admin;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('status') !== TenantStatus::Active->value) {
                return;
            }

            /** @var Tenant|string|int|null $tenant */
            $tenant = $this->route('tenant');
            if (! ($tenant instanceof Tenant)) {
                $tenant = $tenant ? Tenant::find($tenant) : null;
            }

            if (! $tenant) {
                return;
            }

            if (in_array($tenant->status, [TenantStatus::Provisioning->value, TenantStatus::Failed->value], true)) {
                $validator->errors()->add(
                    'status',
                    "No se puede activar un club en estado '{$tenant->status}' cuya base de datos no ha sido provisionada."
                );
            }
        });
    }
}
