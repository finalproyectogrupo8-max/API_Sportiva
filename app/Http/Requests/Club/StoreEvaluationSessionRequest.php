<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Una prueba tomada a toda una categoría el mismo día: un resultado por jugador. */
class StoreEvaluationSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists('tenant.categories', 'id')],
            'evaluated_at' => ['required', 'date'],
            'evaluator_coach_id' => ['nullable', 'integer', Rule::exists('tenant.coaches', 'id')],
            'metric_key' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_]+$/'],
            'metric_name' => ['required', 'string', 'max:120'],
            'unit' => ['nullable', 'string', 'max:30'],
            /** Solo los jugadores que hicieron la prueba. */
            'results' => ['required', 'array', 'min:1', 'max:80'],
            'results.*.player_id' => ['required', 'integer', 'distinct', Rule::exists('tenant.players', 'id')->whereNull('deleted_at')],
            'results.*.numeric_value' => ['nullable', 'numeric', 'between:-9999999,9999999', 'required_without:results.*.text_value'],
            'results.*.text_value' => ['nullable', 'string', 'max:255'],
            'results.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return ['results.*.numeric_value' => 'resultado', 'results.*.player_id' => 'jugador'];
    }
}
