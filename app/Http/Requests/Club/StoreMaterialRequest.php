<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('unit')) {
            $this->merge(['unit' => 'unidades']);
        }
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('tenant.materials', 'name')->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:30'],
            'minimum_stock' => ['sometimes', 'numeric', 'min:0', 'max:9999999999.99'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
