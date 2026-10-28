<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class IndexTenantMembershipRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (in_array($this->input('is_active'), ['true', 'false'], true)) {
            $this->merge(['is_active' => $this->input('is_active') === 'true']);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_active' => ['sometimes', 'boolean'],
            'per_page' => ['sometimes', 'integer'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
