<?php

namespace App\Http\Requests\Club;

use App\Models\Club\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Category $category */
        $category = $this->route('category');
        $sportId = $this->input('sport_id', $category->sport_id);
        $seasonId = $this->input('season_id', $category->season_id);

        return [
            'sport_id' => ['sometimes', 'required', 'integer', Rule::exists('tenant.sports', 'id')],
            'season_id' => ['sometimes', 'required', 'integer', Rule::exists('tenant.seasons', 'id')],
            'name' => [
                'sometimes', 'required', 'string', 'max:80',
                Rule::unique('tenant.categories', 'name')
                    ->where('sport_id', $sportId)
                    ->where('season_id', $seasonId)
                    ->ignore($category),
            ],
            'min_age' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:32767'],
            'max_age' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:32767'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('min_age') || $validator->errors()->has('max_age')) {
                return;
            }

            /** @var Category $category */
            $category = $this->route('category');
            $minAge = $this->input('min_age', $category->min_age);
            $maxAge = $this->input('max_age', $category->max_age);

            if ($minAge !== null && $maxAge !== null && (int) $maxAge < (int) $minAge) {
                $validator->errors()->add('max_age', 'La edad máxima debe ser igual o mayor que la edad mínima.');
            }
        }];
    }
}
