<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

class StoreFacilityComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('condition_status')) {
            $this->merge(['condition_status' => 'ok']);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'condition_status' => ['sometimes', 'string', 'in:ok,review,out_of_service'],
            'last_inspected_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
