<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            $table->string('password')->nullable()->change();
            $table->string('username')->nullable()->unique();
            $table->timestamp('onboarded_at')->nullable();
            $table->foreignId('avatar_file_id')->nullable()->references('id')->on('files')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            $table->dropConstrainedForeignId('avatar_file_id');
            $table->dropUnique(['username']);
            $table->string('password')->nullable(false)->change();

            $table->dropColumn(['onboarded_at', 'username']);
        });
    }
};
