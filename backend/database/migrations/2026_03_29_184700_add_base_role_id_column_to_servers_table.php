<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', static function (Blueprint $table) {
            $table->foreignId('base_role_id')
                ->nullable()
                ->after('user_id')
                ->constrained('server_roles');
        });
    }

    public function down(): void
    {
        Schema::table('servers', static function (Blueprint $table) {
            $table->dropForeign(['base_role_id']);
            $table->dropColumn('base_role_id');
        });
    }
};
