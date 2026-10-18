<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

class IndexSeasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'string', 'max:100'],
            'status' => ['sometimes', 'string', 'in:planned,active,closed'],
            'sort' => ['sometimes', 'string', 'in:name,-name,start_date,-start_date,created_at,-created_at'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
