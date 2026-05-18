<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoMetadata extends Model
{
    use HasFactory;

    protected $table = 'video_metadata';

    protected $fillable = [
        'folder_id',
        'folder_name',
        'sub_folder_name',
        'file_id',
        'video',
        'timestamp',
        'timestamp_seconds',
        'description',
        'vibe_score',
        'semantic_tags'
    ];

    // Beri tahu Laravel bahwa semantic_tags adalah array (JSON)
    protected $casts = [
        'semantic_tags' => 'array',
    ];
}