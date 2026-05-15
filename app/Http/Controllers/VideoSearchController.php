<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class VideoSearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('q');
        $results = [];

        if ($query) {
            // Membaca database JSON yang dibuat oleh Python
            $jsonPath = base_path('video_metadata_index.json');
            
            if (File::exists($jsonPath)) {
                $database = json_decode(File::get($jsonPath), true);
                $keywords = explode(' ', strtolower($query)); // Pisahkan kata kunci

                foreach ($database as $videoName => $videoData) {
                    foreach ($videoData['data'] as $frame) {
                        $desc = strtolower($frame['description']);
                        $match = true;

                        // Pastikan semua kata yang diketik user ada di dalam deskripsi
                        foreach ($keywords as $word) {
                            if (strpos($desc, $word) === false) {
                                $match = false;
                                break;
                            }
                        }

                        // Jika cocok, masukkan ke daftar hasil
                        if ($match) {
                            $results[] = [
                                'video' => $videoName,
                                'timestamp' => $frame['timestamp'],
                                'description' => $frame['description']
                            ];
                        }
                    }
                }
            }
        }

        return view('search', compact('results', 'query'));
    }
}