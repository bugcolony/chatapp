<?php

namespace App\Actions\Direct;

use App\Models\Channel;
use App\Models\User;
use App\Services\Gateway\GatewayEvent;
use App\Services\Gateway\RealtimeTransport;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class LeaveChannel
{
    public function __construct(private RealtimeTransport $transport) {}

    /**
     * @throws Throwable
     */
    public function execute(Channel $channel, User $user): void
    {
        $remaining = DB::transaction(static function () use ($channel, $user): array {
            $channel->participants()->detach($user->id);

            $remaining = $channel->participants()->pluck('users.id')->all();

            if ($remaining === []) {
                $channel->delete();

                return [];
            }

            if ($channel->owner_id === $user->id) {
                $channel->update(['owner_id' => $remaining[0]]);
            }

            return $remaining;
        });

        $this->transport->publish(GatewayEvent::groupChannelRemoved($channel->id, [$user->id]));

        if ($remaining === []) {
            return;
        }

        $channel->refresh()->load('participants');

        $this->transport->publish(GatewayEvent::groupChannelUpdated($channel, $remaining));
    }
}
