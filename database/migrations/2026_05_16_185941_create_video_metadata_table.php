<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_metadata', function (Blueprint $table) {
            $table->id();
            $table->string('folder_id')->index();
            $table->string('folder_name');
            $table->string('sub_folder_name')->nullable(); // 🔥 Kolom Sub-Folder baru
            $table->string('file_id');
            $table->string('video');
            $table->string('timestamp')->nullable();
            $table->integer('timestamp_seconds')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_metadata');
    }
};