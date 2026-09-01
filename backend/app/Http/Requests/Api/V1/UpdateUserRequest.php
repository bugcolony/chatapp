<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'avatar' => ['nullable', 'image', 'max:2048', 'sometimes'],
            'name' => ['string', 'max:32', 'sometimes'],
            'remove_avatar' => ['boolean'],
        ];
    }
}
