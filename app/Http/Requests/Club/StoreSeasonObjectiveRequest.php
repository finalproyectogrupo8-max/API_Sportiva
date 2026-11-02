<?php

namespace App\Http\Requests\Club;

use App\Models\Club\SeasonObjective;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSeasonObjectiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'season_id' => [$required, 'integer', Rule::exists('tenant.seasons', 'id')],
            'category_id' => ['nullable', 'integer', Rule::exists('tenant.categories', 'id')],
            'title' => [$required, 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'target_value' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'current_value' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'unit' => ['nullable', 'string', 'max:30'],
            'status' => ['sometimes', 'string', Rule::in(SeasonObjective::STATUSES)],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
