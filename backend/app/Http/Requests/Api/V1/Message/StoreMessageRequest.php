<?php

namespace App\Http\Requests\Api\V1\Message;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['nullable', 'string', 'max:2000', 'required_without:attachment'],
            'attachment' => [
                'nullable',
                File::default()->max('2mb'),
                'required_without:content',
            ],
            'client_id' => ['required', 'integer'], // ['required', 'string', 'max:255', 'uuid:4']
        ];
    }
}
