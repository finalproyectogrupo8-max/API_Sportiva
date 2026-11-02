<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

class IndexPhysicalEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'player_id' => ['sometimes', 'integer'],
            'category_id' => ['sometimes', 'integer'],
            'metric_key' => ['sometimes', 'string', 'max:80'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
            'sort' => ['sometimes', 'string', 'in:evaluated_at,-evaluated_at'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
