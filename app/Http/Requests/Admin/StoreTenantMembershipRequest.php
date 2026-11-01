<?php

namespace App\Http\Requests\Admin;

use App\Enums\TenantStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTenantMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
                Rule::unique('tenant_memberships', 'user_id')
                    ->where(fn ($query) => $query->where('tenant_id', $tenant->id)),
            ],
            // Slugs de roles del club (GET /api/admin/tenants/{tenant}/roles). Que existan se valida
            // contra la base del club, en ClubMembers.
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string', 'max:80', 'distinct'],
            // IDs de deportes del club (GET /api/sports o base del club).
            'sport_ids' => ['sometimes', 'array'],
            'sport_ids.*' => ['integer', 'distinct'],
            'is_active' => ['prohibited'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->route('tenant')->status !== TenantStatus::Active->value) {
                    $validator->errors()->add('roles', 'El club todavía no está activo: esperá a que termine el alta.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.unique' => 'El usuario ya tiene una membresía en este club.',
            'roles.required' => 'Elegí al menos un rol.',
            'roles.min' => 'Elegí al menos un rol.',
        ];
    }
}
