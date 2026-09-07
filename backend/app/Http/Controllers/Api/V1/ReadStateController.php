<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Message\AckMessage;
use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Message;
use App\Models\Server;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReadStateController extends Controller
{
    public function index(): JsonResponse
    {
        $data = DB::table('message_mentions')
            ->select('channels.server_id', 'messages.channel_id', DB::raw('count(*) as mention_count'))
            ->where('message_mentions.user_id', auth()->id())
            ->join('messages', 'messages.id', '=', 'message_mentions.message_id')
            ->join('channels', fn (JoinClause $j) => $j->on('channels.id', '=', 'messages.channel_id')->whereNull('channels.deleted_at'))
            ->join('members', fn (JoinClause $j) => $j->on('members.server_id', '=', 'channels.server_id')
                ->whereColumn('members.user_id', 'message_mentions.user_id')->whereNull('members.left_at'))
            ->leftJoin('channel_reads', fn (JoinClause $j) => $j->on('channel_reads.channel_id', '=', 'messages.channel_id')->whereColumn('channel_reads.user_id', 'message_mentions.user_id'))
            ->whereRaw('message_mentions.message_id > GREATEST(channel_reads.last_read_id, members.baseline_message_id)')
            ->groupBy('channels.server_id', 'messages.channel_id')
            ->get();

        return response()->json($data);
    }

    public function serverUnread(Server $server): JsonResponse
    {
        $data = DB::table('members')
            ->select(
                'channels.id as channel_id',
                DB::raw('GREATEST(channel_reads.last_read_id, members.baseline_message_id) as last_read_id'),
                'channels.last_message_id as last_message_id',
            )
            ->where('members.user_id', auth()->user()->id)
            ->where('members.server_id', $server->id)
            ->whereNull('members.left_at')
            ->join('channels', function (JoinClause $join) {
                $join->on('channels.server_id', '=', 'members.server_id')
                    ->whereNull('channels.deleted_at', );
            })
            ->leftJoin('channel_reads', function (JoinClause $join) {
                $join->on('channels.id', '=', 'channel_reads.channel_id')
                    ->whereColumn('channel_reads.user_id', 'members.user_id');
            })
            ->get()
        ;

        return response()->json($data);
    }

    public function store(Channel $channel, Message $message, AckMessage $action): JsonResponse
    {
        $action->execute($channel, $message);

        return response()->json(['message' => 'ok']);
    }
}
