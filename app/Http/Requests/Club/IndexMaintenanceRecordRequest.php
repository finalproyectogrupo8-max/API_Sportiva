<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

class IndexMaintenanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'facility_id' => ['sometimes', 'integer'],
            'repair_request_id' => ['sometimes', 'integer'],
            'performed_by' => ['sometimes', 'integer'],
            'from_date' => ['sometimes', 'date'],
            'to_date' => ['sometimes', 'date'],
            'sort' => ['sometimes', 'string', 'in:performed_at,-performed_at,created_at,-created_at'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
