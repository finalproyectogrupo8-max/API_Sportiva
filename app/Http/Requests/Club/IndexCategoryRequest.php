<?php

namespace App\Http\Requests\Club;

use App\Models\Club\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sport_id' => ['sometimes', 'integer'],
            'season_id' => ['sometimes', 'integer'],
            'branch' => ['sometimes', 'string', Rule::in(Category::BRANCHES)],
            'is_active' => ['sometimes', 'boolean'],
            'sort' => ['sometimes', 'string', 'in:name,-name,created_at,-created_at'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
