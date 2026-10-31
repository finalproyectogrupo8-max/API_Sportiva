<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

class IndexRepairRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'facility_id' => ['sometimes', 'integer'],
            'component_id' => ['sometimes', 'integer'],
            'status' => ['sometimes', 'string', 'in:open,assigned,in_progress,resolved,cancelled'],
            'priority' => ['sometimes', 'string', 'in:low,normal,high,urgent'],
            'assigned_to' => ['sometimes', 'integer'],
            'reported_by' => ['sometimes', 'integer'],
            'sort' => ['sometimes', 'string', 'in:reported_at,-reported_at,created_at,-created_at'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
