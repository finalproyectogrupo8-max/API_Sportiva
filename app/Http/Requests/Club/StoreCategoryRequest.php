<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sport_id' => ['required', 'integer', Rule::exists('tenant.sports', 'id')],
            'season_id' => ['required', 'integer', Rule::exists('tenant.seasons', 'id')],
            'name' => [
                'required', 'string', 'max:80',
                Rule::unique('tenant.categories', 'name')
                    ->where('sport_id', $this->input('sport_id'))
                    ->where('season_id', $this->input('season_id')),
            ],
            'min_age' => ['nullable', 'integer', 'min:0', 'max:32767'],
            'max_age' => ['nullable', 'integer', 'min:0', 'max:32767'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->filled('min_age') && $this->filled('max_age') && $this->integer('max_age') < $this->integer('min_age')) {
                $validator->errors()->add('max_age', 'La edad máxima debe ser igual o mayor que la edad mínima.');
            }
        }];
    }
}
