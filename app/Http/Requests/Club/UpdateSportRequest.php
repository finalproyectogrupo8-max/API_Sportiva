<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sport = $this->route('sport');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:80', Rule::unique('tenant.sports', 'name')->ignore($sport)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
