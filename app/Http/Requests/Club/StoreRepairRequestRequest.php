<?php

namespace App\Http\Requests\Club;

use App\Models\Club\FacilityComponent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreRepairRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('priority')) {
            $this->merge(['priority' => 'normal']);
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
            'component_id' => [
                'nullable',
                'integer',
                Rule::exists('tenant.facility_components', 'id'),
            ],
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('tenant.club_users', 'id'),
            ],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string'],
            'priority' => ['sometimes', 'string', 'in:low,normal,high,urgent'],
            'component_condition_status' => ['sometimes', 'string', 'in:review,out_of_service'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->filled('component_id') && $this->filled('facility_id')) {
                    $matches = FacilityComponent::where('id', $this->integer('component_id'))
                        ->where('facility_id', $this->integer('facility_id'))
                        ->exists();

                    if (! $matches) {
                        $validator->errors()->add('component_id', 'El componente seleccionado no pertenece a la instalación indicada.');
                    }
                }
            },
        ];
    }
}
