<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', static function (Blueprint $table) {
            $table->text('content')->nullable()->change();
        });

        Schema::create('message_attachments', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('disk');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->timestamps();

            $table->unique(['disk', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_attachments');

        DB::table('messages')
            ->whereNull('content')
            ->update(['content' => '']);

        Schema::table('messages', static function (Blueprint $table) {
            $table->text('content')->nullable(false)->change();
        });
    }
};
