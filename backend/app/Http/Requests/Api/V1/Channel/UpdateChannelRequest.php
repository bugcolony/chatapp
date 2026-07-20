<?php

namespace App\Http\Requests\Api\V1\Channel;

use App\Enums\ChannelType;
use App\Models\Channel;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Channel $channel */
        $channel = $this->route('channel');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'parent_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('channels', 'id')->where(
                    fn (Builder $query) => $query
                        ->where('server_id', $channel->server_id)
                        ->where('type', ChannelType::Category->value)
                        ->whereNull('deleted_at'),
                ),
            ],
        ];
    }
}
