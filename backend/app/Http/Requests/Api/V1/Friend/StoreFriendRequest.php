<?php

namespace App\Http\Requests\Api\V1\Friend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreFriendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('username'))) {
            $this->merge(['username' => Str::lower(trim($this->input('username')))]);
        }
    }

    public function rules(): array
    {
        return [
            'username' => [
                'required',
                'string',
                Rule::notIn([$this->user()->username]),
                Rule::exists('users', 'username')->whereNull('banned_at')->whereNull('closed_at'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'username.not_in' => 'User not found',
            'username.exists' => 'User not found',
        ];
    }
}
