<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', static function (Blueprint $table) {
            $table->index(['channel_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::table('messages', static function (Blueprint $table) {
            $table->dropIndex(['channel_id', 'id']);
        });
    }
};
