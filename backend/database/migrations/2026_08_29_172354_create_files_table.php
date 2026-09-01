<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', static function (Blueprint $table) {
            $table->id();
            $table->string('disk');
            $table->string('source_path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');

            $table->string('path')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->json('variants')->nullable();
            $table->string('status')->default('ready');

            $table->timestamps();

            $table->unique(['disk', 'source_path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
