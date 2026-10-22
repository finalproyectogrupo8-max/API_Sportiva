<?php

namespace App\Http\Requests\Admin;

use App\Enums\MembershipRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['sometimes', Rule::enum(MembershipRole::class)],
            'is_active' => ['sometimes', 'boolean'],
            'user_id' => ['prohibited'],
            'tenant_id' => ['prohibited'],
        ];
    }
}
