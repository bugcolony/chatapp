<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', static function (Blueprint $table) {
            $table->unsignedBigInteger('baseline_message_id')->default(0);
        });

        DB::statement('update members set baseline_message_id = (select coalesce(max(id), 0) from messages)');
    }

    public function down(): void
    {
        Schema::table('members', static function (Blueprint $table) {
            $table->dropColumn('baseline_message_id');
        });
    }
};
