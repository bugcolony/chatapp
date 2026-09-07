<?php

namespace App\Http\Requests\Api\V1\Channel;

use App\Enums\SummaryRange;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SummarizeChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'range' => ['required', Rule::enum(SummaryRange::class)],
            'timezone' => ['nullable', 'timezone'],
        ];
    }
}
