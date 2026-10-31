<?php

namespace App\Http\Requests\Club;

use App\Models\Club\FacilityComponent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateRepairRequestRequest extends FormRequest
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
            'component_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('tenant.facility_components', 'id'),
            ],
            'assigned_to' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('tenant.club_users', 'id'),
            ],
            'title' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['sometimes', 'required', 'string'],
            'priority' => ['sometimes', 'required', 'string', 'in:low,normal,high,urgent'],
            'status' => ['sometimes', 'required', 'string', 'in:open,assigned,in_progress,resolved,cancelled'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $repairRequest = $this->route('repair_request');
                $facilityId = $this->filled('facility_id') ? $this->integer('facility_id') : $repairRequest?->facility_id;

                if ($this->filled('component_id') && $facilityId) {
                    $matches = FacilityComponent::where('id', $this->integer('component_id'))
                        ->where('facility_id', $facilityId)
                        ->exists();

                    if (! $matches) {
                        $validator->errors()->add('component_id', 'El componente seleccionado no pertenece a la instalación indicada.');
                    }
                }
            },
        ];
    }
}
