<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_permission_overrides', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('channels');
            $table->foreignId('server_role_id')->nullable()->constrained('server_roles');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->unsignedBigInteger('allow')->default(0);
            $table->unsignedBigInteger('deny')->default(0);
            $table->timestamps();

            $table->unique(['channel_id', 'server_role_id']);
            $table->unique(['channel_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_permission_overrides');
    }
};
