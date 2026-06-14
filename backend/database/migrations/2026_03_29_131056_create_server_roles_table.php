<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_roles', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained();
            $table->string('name');
            $table->string('color')->nullable();
            $table->unsignedBigInteger('permissions')->default(0);
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->unique(['server_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_roles');
    }
};
