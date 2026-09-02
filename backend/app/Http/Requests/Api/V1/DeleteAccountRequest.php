<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DeleteAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ! $this->user()->isDemo();
    }

    protected function failedAuthorization(): void
    {
        throw new AuthorizationException('Demo accounts cannot be deleted.');
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('username'))) {
            $this->merge(['username' => Str::lower(trim($this->input('username')))]);
        }
    }

    public function rules(): array
    {
        $username = $this->user()->username;

        if ($username === null) {
            return [];
        }

        return [
            'username' => [
                'required',
                'string',
                Rule::in([$username]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'username.in' => 'That does not match your username.',
        ];
    }
}
