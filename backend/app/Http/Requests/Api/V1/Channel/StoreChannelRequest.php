<?php

namespace App\Http\Requests\Api\V1\Channel;

use App\Enums\ChannelType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', Rule::enum(ChannelType::class)],
            'parent_id' => ['nullable', 'integer', 'exists:channels,id'],
        ];
    }
}
