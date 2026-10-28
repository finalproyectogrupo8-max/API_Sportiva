<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'club_name' => ['nullable', 'string', 'max:150'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
            'website' => ['nullable', 'string'],
            // Inmutables y de servidor: rechazar explícitamente si se envían
            'status' => ['prohibited'],
            'admin_notes' => ['prohibited'],
            'handled_by' => ['prohibited'],
            'resolved_at' => ['prohibited'],
        ];
    }
}

