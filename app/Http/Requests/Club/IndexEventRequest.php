<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

/** Filtros de listados de partidos y entrenamientos. */
class IndexEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'integer'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date', 'after_or_equal:from'],
            'status' => ['sometimes', 'string'],
            'sort' => ['sometimes', 'string', 'in:date,-date'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
