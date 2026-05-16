<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoMetadata extends Model
{
    use HasFactory;
    protected $table = 'video_metadata';
    protected $fillable = ['folder_id', 'folder_name', 'sub_folder_name', 'file_id', 'video', 'timestamp', 'timestamp_seconds', 'description'];
}