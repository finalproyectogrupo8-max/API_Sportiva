<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaintenanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'facility_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('tenant.facilities', 'id')->whereNull('deleted_at'),
            ],
            'repair_request_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('tenant.repair_requests', 'id')->whereNull('deleted_at'),
            ],
            'performed_by' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('tenant.club_users', 'id'),
            ],
            'performed_at' => ['sometimes', 'required', 'date'],
            'description' => ['sometimes', 'required', 'string'],
            'cost' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9999999999.99'],
        ];
    }
}
