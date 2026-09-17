<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channels', static function (Blueprint $table) {
            $table->unsignedBigInteger('server_id')->nullable()->change();
            $table->string('name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('channels', static function (Blueprint $table) {
            $table->unsignedBigInteger('server_id')->nullable(false)->change();
            $table->string('name')->nullable(false)->change();
        });
    }
};
