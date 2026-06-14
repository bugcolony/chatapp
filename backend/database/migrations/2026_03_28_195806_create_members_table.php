<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('server_id')->constrained();
            $table->string('nickname')->nullable();
            $table->timestamp('left_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['user_id', 'server_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
