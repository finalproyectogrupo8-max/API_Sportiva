<?php

namespace App\Http\Requests\Admin;

use App\Enums\MembershipRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'role' => ['required', Rule::enum(MembershipRole::class)],
            'is_active' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.unique' => 'El usuario ya tiene una membresía en este club.',
        ];
    }
}
