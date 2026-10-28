<?php

namespace App\Http\Requests\Admin;

use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'required', Rule::enum(TicketStatus::class)],
            'admin_notes' => ['sometimes', 'nullable', 'string'],
            // Inmutables tras la creación
            'name' => ['prohibited'],
            'email' => ['prohibited'],
            'club_name' => ['prohibited'],
            'subject' => ['prohibited'],
            'message' => ['prohibited'],
            'website' => ['prohibited'],
            'handled_by' => ['prohibited'],
            'resolved_at' => ['prohibited'],
        ];
    }
}
