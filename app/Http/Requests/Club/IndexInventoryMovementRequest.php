<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexInventoryMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'material_id' => ['sometimes', 'integer', Rule::exists('tenant.materials', 'id')],
            'location_id' => ['sometimes', 'integer', Rule::exists('tenant.storage_locations', 'id')],
            'movement_type' => ['sometimes', 'string', 'in:in,out,adjustment,transfer_in,transfer_out'],
            'transfer_group_id' => ['sometimes', 'string', 'uuid'],
            'sort' => ['sometimes', 'string', 'in:created_at,-created_at'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
