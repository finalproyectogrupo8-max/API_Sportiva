<?php

namespace App\Http\Requests\Club;

use App\Models\Club\Competition;
use App\Models\Club\Season;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Alta y edición de competencias con sus requisitos y categorías. En PATCH, `requirements` y
 * `category_ids` reemplazan la lista completa si vienen; si no vienen, no se tocan.
 */
class StoreCompetitionRequest extends FormRequest
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
        abort_if($this->user()->clubAccess()->has('competitions.update') && $competition instanceof Competition && ! Competition::query()->visibleTo($this->user()->clubAccess())->whereKey($competition->getKey())->exists(), 404);
    }

    public function rules(): array
    {
        $req = $this->isMethod('POST') ? ['required'] : ['sometimes', 'required'];
        $opt = $this->isMethod('POST') ? [] : ['sometimes'];

        return [
            'sport_id' => [...$req, 'integer', Rule::exists('tenant.sports', 'id')],
            'season_id' => [...$req, 'integer', Rule::exists('tenant.seasons', 'id')],
            'name' => [...$req, 'string', 'max:150'],
            'organizer' => [...$opt, 'nullable', 'string', 'max:150'],
            'registration_deadline' => [...$opt, 'nullable', 'date'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'distinct', Rule::exists('tenant.categories', 'id')],
            'requirements' => ['sometimes', 'array', 'max:30'],
            // Con id se edita ese requisito; sin id se crea. Los que no vienen se borran.
            'requirements.*.id' => ['sometimes', 'integer'],
            'requirements.*.name' => ['required', 'string', 'max:120'],
            'requirements.*.validity_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'requirements.*.requires_file' => ['sometimes', 'boolean'],
            'requirements.*.minors_only' => ['sometimes', 'boolean'],
            'requirements.*.is_required' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * El deporte tiene que ser uno de los suyos (con alcance "sus deportes") y la temporada, de ese
     * deporte o de todo el club.
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            /** @var Competition|null $competition */
            $competition = $this->route('competition');
            $permission = $competition ? 'competitions.update' : 'competitions.create';
            $sportId = $this->filled('sport_id') ? $this->integer('sport_id') : $competition?->sport_id;
            $seasonId = $this->filled('season_id') ? $this->integer('season_id') : $competition?->season_id;

            if ($competition && ! $this->hasAny(['sport_id', 'season_id'])) {
                return;
            }

            $access = $this->user()->clubAccess();
            if ($sportId !== null && $access->has($permission) && ! $validator->errors()->has('sport_id') && ! $access->allowsSport($permission, $sportId)) {
                $validator->errors()->add('sport_id', 'Elegí uno de los deportes que coordinás.');
            }

            $season = $seasonId ? Season::find($seasonId) : null;
            if ($sportId !== null && $season && ! $validator->errors()->has('season_id') && ! $season->acceptsSport($sportId)) {
                $validator->errors()->add('season_id', 'La temporada es de otro deporte.');
            }
        }];
    }
}
