<?php

namespace App\Http\Requests\Admin;

use App\Enums\TenantStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateTenantMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Reemplaza la lista completa de roles del miembro en el club.
            'roles' => ['sometimes', 'array', 'min:1'],
            'roles.*' => ['required', 'string', 'max:80', 'distinct'],
            'sport_ids' => ['sometimes', 'array'],
            'sport_ids.*' => ['integer', 'distinct'],
            'is_active' => ['sometimes', 'boolean'],
            'user_id' => ['prohibited'],
            'tenant_id' => ['prohibited'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (($this->has('roles') || $this->has('sport_ids')) && $this->route('tenant')->status !== TenantStatus::Active->value) {
                    $validator->errors()->add('roles', 'El club todavía no está activo: esperá a que termine el alta.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'roles.min' => 'Elegí al menos un rol.',
        ];
    }
}
