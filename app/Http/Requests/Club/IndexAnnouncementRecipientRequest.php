<?php

namespace App\Http\Requests\Club;

use App\Models\Club\AnnouncementRecipient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAnnouncementRecipientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', Rule::in([AnnouncementRecipient::PENDING, AnnouncementRecipient::SENT, AnnouncementRecipient::FAILED])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
