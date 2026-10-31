<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('performed_at')) {
            $this->merge(['performed_at' => now()->toDateTimeString()]);
        }
    }

    public function rules(): array
    {
        return [
            'facility_id' => [
                'required',
                'integer',
                Rule::exists('tenant.facilities', 'id')->whereNull('deleted_at'),
            ],
            'repair_request_id' => [
                'nullable',
                'integer',
                Rule::exists('tenant.repair_requests', 'id')->whereNull('deleted_at'),
            ],
            'performed_by' => [
                'nullable',
                'integer',
                Rule::exists('tenant.club_users', 'id'),
            ],
            'performed_at' => ['sometimes', 'date'],
            'description' => ['required', 'string'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
        ];
    }
}
