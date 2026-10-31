<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'material_id' => [
                'required',
                'integer',
                Rule::exists('tenant.materials', 'id')->whereNull('deleted_at'),
            ],
            'from_location_id' => [
                'required',
                'integer',
                Rule::exists('tenant.storage_locations', 'id')->whereNull('deleted_at'),
            ],
            'to_location_id' => [
                'required',
                'integer',
                'different:from_location_id',
                Rule::exists('tenant.storage_locations', 'id')->whereNull('deleted_at'),
            ],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'reason' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
        ];
    }
}
