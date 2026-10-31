<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $material = $this->route('material');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:120',
                Rule::unique('tenant.materials', 'name')
                    ->whereNull('deleted_at')
                    ->ignore($material),
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'unit' => ['sometimes', 'required', 'string', 'max:30'],
            'minimum_stock' => ['sometimes', 'numeric', 'min:0', 'max:9999999999.99'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
