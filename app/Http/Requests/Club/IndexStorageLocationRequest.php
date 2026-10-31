<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

class IndexStorageLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'sort' => ['sometimes', 'string', 'in:name,-name,location_code,-location_code,created_at,-created_at'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
