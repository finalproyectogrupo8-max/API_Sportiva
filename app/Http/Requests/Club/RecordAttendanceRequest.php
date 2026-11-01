<?php

namespace App\Http\Requests\Club;

use App\Models\Club\PlayerAttendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Asistencia de un evento en un solo request (la pantalla móvil la manda entera). */
class RecordAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'records' => ['required', 'array', 'min:1', 'max:200'],
            'records.*.player_id' => ['required', 'integer', 'distinct'],
            'records.*.status' => ['required', 'string', Rule::in(PlayerAttendance::STATUSES)],
            'records.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
