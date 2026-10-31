<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStorageLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'location_code' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('tenant.storage_locations', 'location_code')->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
