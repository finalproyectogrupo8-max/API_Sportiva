<?php

namespace App\Http\Requests\Admin;

use App\Enums\TenantStatus;
use App\Http\Requests\Concerns\TenantProfileRules;
use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTenantRequest extends FormRequest
{
    use TenantProfileRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            ...$this->tenantProfileRules(),
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

    public function messages(): array
    {
        return $this->tenantProfileMessages();
    }

    /**
     * Un club en provisioning o failed no tiene la base lista: su estado solo lo cambia el job
     * ProvisionTenant. Si se permitiera pasarlo a suspended, después se podría activar con
     * suspended → active sin que exista la base.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->has('status')) {
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
                    "No se puede cambiar el estado de un club en '{$tenant->status}': su base de datos no está provisionada."
                );
            }
        });
    }
}
