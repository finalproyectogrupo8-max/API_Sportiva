<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

class IndexOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', 'in:apparel,material'],
            'status' => ['nullable', 'string', 'in:draft,submitted,pending,approved,preparing,partially_delivered,delivered,rejected'],
            'category_id' => ['nullable', 'integer'],
            'requested_by' => ['nullable', 'integer'],
            'priority' => ['nullable', 'string', 'in:low,normal,high,urgent'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', 'string'],
        ];
    }
}
