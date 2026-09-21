<?php

namespace App\Http\Requests\Api\V1\Direct;

use Illuminate\Foundation\Http\FormRequest;

class StoreGroupParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
