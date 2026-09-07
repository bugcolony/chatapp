<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channels', static function (Blueprint $table) {
            $table->unsignedBigInteger('last_message_id')->default(0);
        });

        DB::statement('update channels set last_message_id = coalesce((
            select max(messages.id) from messages
            where messages.channel_id = channels.id and messages.deleted_at is null
        ), 0)');
    }

    public function down(): void
    {
        Schema::table('channels', static function (Blueprint $table) {
            $table->dropColumn('last_message_id');
        });
    }
};
