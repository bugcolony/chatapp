<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ChannelMember
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $channel = $request->route('channel');

        if (! $channel) {
            return response()->json(['message' => 'Channel not found'], 404);
        }

        $isMember = auth()->user()->memberships()->active()->where('server_id', $channel->server_id)->exists();

        abort_unless($isMember, 403, 'You are not a member of this channel');

        return $next($request);
    }
}
