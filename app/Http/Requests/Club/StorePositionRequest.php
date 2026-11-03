<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sport_id' => ['required', 'integer', 'exists:tenant.sports,id'],
            'name' => [
                'required',
                'string',
                'max:80',
                Rule::unique('tenant.positions', 'name')->where('sport_id', $this->input('sport_id')),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
