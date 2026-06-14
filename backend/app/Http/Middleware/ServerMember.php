<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ServerMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $server = $request->route('server');

        if (! $server) {
            return response()->json(['message' => 'Server not found'], 404);
        }

        $isMember = auth()->user()->memberships()->active()->where('server_id', $server->id)->exists();

        abort_unless($isMember, 403, 'You are not a member of this server');

        return $next($request);
    }
}
