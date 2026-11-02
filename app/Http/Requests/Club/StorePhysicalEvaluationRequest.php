<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Alta o edición de una evaluación física con sus mediciones (TIZ-20). En la edición, las mediciones reemplazan a las anteriores. */
class StorePhysicalEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $creating = $this->isMethod('post');
        $required = $creating ? 'required' : 'sometimes';

        return [
            'player_id' => [$required, 'integer', Rule::exists('tenant.players', 'id')->whereNull('deleted_at')],
            'category_id' => ['nullable', 'integer', Rule::exists('tenant.categories', 'id')],
            'evaluator_coach_id' => ['nullable', 'integer', Rule::exists('tenant.coaches', 'id')],
            'evaluated_at' => [$required, 'date'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'recommendations' => ['nullable', 'string', 'max:2000'],
            'measurements' => [$required, 'array', 'min:1', 'max:30'],
            'measurements.*.metric_key' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_]+$/', 'distinct'],
            'measurements.*.metric_name' => ['required', 'string', 'max:120'],
            'measurements.*.numeric_value' => ['nullable', 'numeric', 'between:-9999999,9999999', 'required_without:measurements.*.text_value'],
            'measurements.*.unit' => ['nullable', 'string', 'max:30'],
            'measurements.*.text_value' => ['nullable', 'string', 'max:255'],
            'measurements.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'measurements.*.metric_key' => 'código de la prueba',
            'measurements.*.metric_name' => 'nombre de la prueba',
            'measurements.*.numeric_value' => 'valor',
            'measurements.*.text_value' => 'resultado',
        ];
    }
}
