<?php

namespace App\Http\Requests\Club;

use App\Models\Club\Competition;
use App\Models\Club\Season;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Copiar una competencia a otra temporada: trae los requisitos, no las categorías. */
class CopyCompetitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Fuera de su alcance, 404 antes de validar: los errores no revelan que el registro existe. */
    protected function prepareForValidation(): void
    {
        $competition = $this->route('competition');
        // Sin el permiso de cambiarlo, que responda el 403 del controlador.
        abort_if($this->user()->clubAccess()->has('competitions.create') && $competition instanceof Competition && ! Competition::query()->visibleTo($this->user()->clubAccess())->whereKey($competition->getKey())->exists(), 404);
    }

    public function rules(): array
    {
        return [
            'season_id' => ['required', 'integer', Rule::exists('tenant.seasons', 'id')],
            'name' => ['sometimes', 'string', 'max:150'],
            'registration_deadline' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /** La copia queda en el mismo deporte: la temporada tiene que ser de ese deporte o del club. */
    public function after(): array
    {
        return [function (Validator $validator): void {
            /** @var Competition $competition */
            $competition = $this->route('competition');
            $season = $this->filled('season_id') ? Season::find($this->integer('season_id')) : null;

            if ($season && ! $validator->errors()->has('season_id') && ! $season->acceptsSport($competition->sport_id)) {
                $validator->errors()->add('season_id', 'La temporada es de otro deporte.');
            }
        }];
    }
}
