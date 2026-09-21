<?php

namespace App\Http\Requests\Api\V1\Direct;

use App\Actions\Direct\CreateGroupChannel;
use Illuminate\Foundation\Http\FormRequest;

class StoreGroupChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'participant_ids' => ['required', 'array', 'min:1', 'max:'.(CreateGroupChannel::MAX_PARTICIPANTS - 1)],
            'participant_ids.*' => ['integer', 'exists:users,id'],
        ];
    }
}
