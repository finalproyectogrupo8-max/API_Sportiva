<?php

namespace App\Http\Requests\Club;

use App\Models\Club\Category;
use App\Models\Club\Season;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Fuera de su alcance, 404 antes de validar: los errores no revelan que el registro existe. */
    protected function prepareForValidation(): void
    {
        $category = $this->route('category');
        // Sin el permiso de cambiarlo, que responda el 403 del controlador.
        abort_if($this->user()->clubAccess()->has('categories.update') && $category instanceof Category && ! Category::query()->visibleTo($this->user()->clubAccess())->whereKey($category->getKey())->exists(), 404);
    }

    public function rules(): array
    {
        /** @var Category $category */
        $category = $this->route('category');
        $sportId = $this->input('sport_id', $category->sport_id);
        $seasonId = $this->input('season_id', $category->season_id);

        return [
            'sport_id' => ['sometimes', 'required', 'integer', Rule::exists('tenant.sports', 'id')],
            'season_id' => ['sometimes', 'required', 'integer', Rule::exists('tenant.seasons', 'id')],
            'name' => [
                'sometimes', 'required', 'string', 'max:80',
                Rule::unique('tenant.categories', 'name')
                    ->where('sport_id', $sportId)
                    ->where('season_id', $seasonId)
                    ->ignore($category),
            ],
            'min_age' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:32767'],
            'max_age' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:32767'],
            // TIZ-40: `female`, `male` o `mixed`; opcional.
            'branch' => ['sometimes', 'nullable', 'string', Rule::in(Category::BRANCHES)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            /** @var Category $category */
            $category = $this->route('category');

            if ($this->hasAny(['sport_id', 'season_id'])) {
                $this->validateSportAndSeason(
                    $validator,
                    'categories.update',
                    (int) $this->input('sport_id', $category->sport_id),
                    (int) $this->input('season_id', $category->season_id),
                );
            }

            if ($validator->errors()->has('min_age') || $validator->errors()->has('max_age')) {
                return;
            }

            $minAge = $this->input('min_age', $category->min_age);
            $maxAge = $this->input('max_age', $category->max_age);

            if ($minAge !== null && $maxAge !== null && (int) $maxAge < (int) $minAge) {
                $validator->errors()->add('max_age', 'La edad máxima debe ser igual o mayor que la edad mínima.');
            }
        }];
    }

    /**
     * El deporte tiene que ser uno de los suyos (con alcance "sus deportes") y la temporada, de ese
     * deporte o de todo el club.
     */
    private function validateSportAndSeason(Validator $validator, string $permission, ?int $sportId, ?int $seasonId): void
    {
        $access = $this->user()->clubAccess();
        if ($sportId !== null && $access->has($permission) && ! $validator->errors()->has('sport_id') && ! $access->allowsSport($permission, $sportId)) {
            $validator->errors()->add('sport_id', 'Elegí uno de los deportes que coordinás.');
        }

        $season = $seasonId ? Season::find($seasonId) : null;
        if ($sportId !== null && $season && ! $validator->errors()->has('season_id') && ! $season->acceptsSport($sportId)) {
            $validator->errors()->add('season_id', 'La temporada es de otro deporte.');
        }
    }
}
