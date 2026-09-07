<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Message\SummarizeChannel;
use App\Enums\SummaryRange;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Channel\SummarizeChannelRequest;
use App\Models\Channel;
use Illuminate\Http\JsonResponse;
use Throwable;

class ChannelSummaryController extends Controller
{
    public function __invoke(SummarizeChannelRequest $request, Channel $channel, SummarizeChannel $action): JsonResponse
    {
        try {
            $summary = $action->execute(
                $channel,
                SummaryRange::from($request->safe()->input('range')),
                $request->safe()->input('timezone'),
            );
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'Could not generate a summary right now.'], 503);
        }

        return response()->json($summary ?? ['summary' => null]);
    }
}
