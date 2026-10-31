<?php

namespace App\Http\Requests\Club;

use App\Models\Club\GameMatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Alta y edición de partidos. En PATCH todo es opcional; el resultado se carga así. */
class StoreMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $req = $this->isMethod('POST') ? ['required'] : ['sometimes', 'required'];
        $opt = $this->isMethod('POST') ? [] : ['sometimes'];

        return [
            'category_id' => [...$req, 'integer', Rule::exists('tenant.categories', 'id')],
            'competition_id' => [...$opt, 'nullable', 'integer', Rule::exists('tenant.competitions', 'id')->whereNull('deleted_at')],
            'opponent' => [...$req, 'string', 'max:120'],
            'match_date' => [...$req, 'date'],
            // Hora de presentación: antes del partido.
            'meet_at' => [...$opt, 'nullable', 'date', 'before_or_equal:match_date'],
            'venue' => [...$opt, 'nullable', 'string', 'max:200'],
            'maps_url' => [...$opt, 'nullable', 'url', 'max:500'],
            'is_home' => [...$req, 'boolean'],
            'uniform' => [...$opt, 'nullable', 'string', 'in:home,away'],
            'uniform_color' => [...$opt, 'nullable', 'string', 'max:50'],
            'what_to_bring' => [...$opt, 'nullable', 'string', 'max:1000'],
            'our_score' => [...$opt, 'nullable', 'integer', 'min:0', 'max:999'],
            'opponent_score' => [...$opt, 'nullable', 'integer', 'min:0', 'max:999'],
            'status' => ['sometimes', 'string', Rule::in(GameMatch::STATUSES)],
            'notes' => [...$opt, 'nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return ['meet_at.before_or_equal' => 'La hora de presentación tiene que ser antes del partido.'];
    }
}
