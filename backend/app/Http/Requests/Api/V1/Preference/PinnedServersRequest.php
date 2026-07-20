<?php

namespace App\Http\Requests\Api\V1\Preference;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PinnedServersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'server_ids' => ['present', 'array', 'exists:servers,id', "min:0"],
        ];
    }
}
