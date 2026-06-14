<?php

namespace App\Http\Requests\Api\V1\Invite;

use Illuminate\Foundation\Http\FormRequest;

class StoreServerInviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'max_uses' => ['sometimes', 'numeric', 'nullable'],
            'expires_at' => ['sometimes', 'date', 'nullable'],
        ];
    }
}
