<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreInventoryMovementRequest extends FormRequest
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
            'location_id' => [
                'required',
                'integer',
                Rule::exists('tenant.storage_locations', 'id')->whereNull('deleted_at'),
            ],
            'movement_type' => ['required', 'string', 'in:in,out,adjustment'],
            'quantity' => ['required', 'numeric'],
            'reason' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $type = $this->input('movement_type');
                $qty = $this->input('quantity');

                if (! is_numeric($qty)) {
                    return;
                }

                $qty = (float) $qty;

                if (in_array($type, ['in', 'out'], true) && $qty <= 0) {
                    $validator->errors()->add('quantity', 'La cantidad para ingresos y egresos debe ser mayor a 0.');
                }

                if ($type === 'adjustment' && $qty == 0.0) {
                    $validator->errors()->add('quantity', 'La cantidad del ajuste no puede ser cero.');
                }
            },
        ];
    }
}
