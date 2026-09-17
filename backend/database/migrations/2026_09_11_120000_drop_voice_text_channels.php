<?php

use App\Models\File;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $voiceText = DB::table('channels')->select('id')->where('type', 'voice_text');
        $messages = DB::table('messages')->select('id')->whereIn('channel_id', $voiceText);

        File::query()
            ->whereIn('id', DB::table('message_attachments')->select('file_id')->whereIn('message_id', $messages))
            ->get()
            ->each->delete();

        DB::table('messages')->whereIn('channel_id', $voiceText)->delete();
        DB::table('channel_permission_overrides')->whereIn('channel_id', $voiceText)->delete();
        DB::table('channels')->where('type', 'voice_text')->delete();
    }
};
