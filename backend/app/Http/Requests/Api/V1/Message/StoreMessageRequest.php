<?php

namespace App\Http\Requests\Api\V1\Message;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:2000'],
            'client_id' => ['required', 'integer'], // ['required', 'string', 'max:255', 'uuid:4']
        ];
    }
}
