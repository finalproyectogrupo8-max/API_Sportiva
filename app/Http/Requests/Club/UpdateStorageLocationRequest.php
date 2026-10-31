<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStorageLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $storageLocation = $this->route('storage_location');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'location_code' => [
                'sometimes',
                'nullable',
                'string',
                'max:40',
                Rule::unique('tenant.storage_locations', 'location_code')
                    ->whereNull('deleted_at')
                    ->ignore($storageLocation),
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
