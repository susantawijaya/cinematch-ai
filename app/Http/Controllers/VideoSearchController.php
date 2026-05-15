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
            // Membaca database JSON
            $jsonPath = base_path('video_metadata_index.json');
            
            if (File::exists($jsonPath)) {
                $database = json_decode(File::get($jsonPath), true);
                $keywords = explode(' ', strtolower($query)); // Pisahkan kata kunci

                // Pastikan databasenya terbaca sebagai array
                if (is_array($database)) {
                    foreach ($database as $key => $entry) {
                        
                        // Deteksi otomatis: Format JSON Baru (Cloud) atau Lama?
                        if (isset($entry['filename']) || isset($entry['matches'])) {
                            // Format Baru dari Google Drive
                            $videoName = $entry['filename'] ?? 'Unknown Video';
                            $frames = $entry['matches'] ?? [];
                        } else {
                            // Format Lama
                            $videoName = is_string($key) ? $key : 'Unknown Video';
                            $frames = $entry['data'] ?? [];
                        }

                        // Loop setiap momen/frame dalam video tersebut
                        if (is_array($frames)) {
                            foreach ($frames as $frame) {
                                // Pastikan frame memiliki deskripsi agar tidak error
                                if (!isset($frame['description'])) continue;

                                $desc = strtolower($frame['description']);
                                $match = true;

                                // Pastikan SEMUA kata yang diketik user ada di dalam deskripsi
                                foreach ($keywords as $word) {
                                    if (strpos($desc, $word) === false) {
                                        $match = false;
                                        break;
                                    }
                                }

                                // Jika cocok, masukkan ke daftar hasil untuk ditampilkan
                                if ($match) {
                                    $results[] = [
                                        'video' => $videoName,
                                        'timestamp' => $frame['timestamp'] ?? '00:00',
                                        'description' => $frame['description']
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        return view('search', compact('results', 'query'));
    }
}