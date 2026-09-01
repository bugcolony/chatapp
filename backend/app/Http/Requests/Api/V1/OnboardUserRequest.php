<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OnboardUserRequest extends FormRequest
{
    public const array RESERVED_USERNAMES = [
        'about', 'admin', 'administrator', 'api', 'app', 'auth', 'billing',
        'blog', 'channel', 'channels', 'contact', 'dashboard', 'docs', 'everyone',
        'help', 'here', 'invite', 'invites', 'login', 'logout', 'me', 'mod',
        'moderator', 'onboarding', 'owner', 'privacy', 'register', 'root',
        'security', 'server', 'servers', 'settings', 'signup', 'staff', 'status',
        'support', 'system', 'terms', 'user', 'users',
    ];

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
                'min:3',
                'max:32',
                'regex:/^[a-z0-9._]+$/',
                Rule::notIn(self::RESERVED_USERNAMES),
                Rule::unique('users', 'username'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'username.regex' => 'The username may only contain lowercase letters, numbers, dots and underscores.',
            'username.not_in' => 'That username is taken.',
        ];
    }
}
