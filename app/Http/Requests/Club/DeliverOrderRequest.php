<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeliverOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('deliveries') && $this->has('items')) {
            $this->merge(['deliveries' => $this->input('items')]);
        }
    }

    public function rules(): array
    {
        return [
            'location_id' => [
                'nullable',
                'integer',
                Rule::exists('tenant.storage_locations', 'id')->whereNull('deleted_at'),
            ],
            'comment' => ['nullable', 'string', 'max:255'],
            'deliveries' => ['nullable', 'array'],
            'deliveries.*.id' => ['required_with:deliveries', 'integer'],
            'deliveries.*.quantity' => ['sometimes', 'numeric', 'gt:0'],
            'deliveries.*.delivered_quantity' => ['sometimes', 'numeric', 'gte:0'],
        ];
    }
}
