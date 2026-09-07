<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            create or replace function bump_channel_last_message() returns trigger
            language plpgsql as $$
            begin
                update channels
                set last_message_id = greatest(last_message_id, new.id)
                where id = new.channel_id;
                return new;
            end;
            $$;
        SQL);

        DB::unprepared('create trigger messages_bump_channel_last_message
            after insert on messages
            for each row execute function bump_channel_last_message();');
    }

    public function down(): void
    {
        DB::unprepared('drop trigger if exists messages_bump_channel_last_message on messages');
        DB::unprepared('drop function if exists bump_channel_last_message()');
    }
};
