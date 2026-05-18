<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video_metadata', function (Blueprint $table) {
            // Menambahkan kolom vibe_score dan semantic_tags jika belum ada
            if (!Schema::hasColumn('video_metadata', 'vibe_score')) {
                $table->integer('vibe_score')->nullable()->after('description');
            }
            if (!Schema::hasColumn('video_metadata', 'semantic_tags')) {
                $table->json('semantic_tags')->nullable()->after('vibe_score');
            }
        });
    }

    public function down(): void
    {
        Schema::table('video_metadata', function (Blueprint $table) {
            $table->dropColumn(['vibe_score', 'semantic_tags']);
        });
    }
};