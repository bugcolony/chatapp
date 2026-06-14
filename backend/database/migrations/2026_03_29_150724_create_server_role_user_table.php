<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_role_user', static function (Blueprint $table) {
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('server_role_id')->constrained()->cascadeOnDelete();

            $table->primary(['server_id', 'user_id', 'server_role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_role_user');
    }
};
