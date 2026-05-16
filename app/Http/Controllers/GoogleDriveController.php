<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Http;
use App\Models\VideoMetadata;

class GoogleDriveController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->scopes(['https://www.googleapis.com/auth/drive.readonly'])
            ->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $user = Socialite::driver('google')->stateless()->user();
            session(['google_token' => $user->token]);
            
            return redirect('/search')->with('success', 'Berhasil terhubung ke Google Drive!');
        } catch (\Exception $e) {
            return redirect('/')->with('error', 'Gagal menghubungkan akun Google: ' . $e->getMessage());
        }
    }

    // 🔥 Tarik semua video mentah dari folder Drive secara instan tanpa AI dahulu
    public function connectFolder(Request $request)
    {
        $folderId = $request->input('folder_id');
        $token = session('google_token');

        if (!$token) {
            return redirect('/search')->with('error', 'Token Google tidak ditemukan. Silakan hubungkan ulang.');
        }

        // 1. Ambil Nama Folder Asli
        $folderName = 'Untitled Workspace';
        $repoFolder = Http::withToken($token)->get("https://www.googleapis.com/drive/v3/files/{$folderId}?fields=name");
        if ($repoFolder->successful()) {
            $folderName = $repoFolder->json()['name'] ?? 'Untitled Workspace';
        }

        // 2. Ambil Semua File Video Mentah di Dalam Folder Tersebut
        $query = urlencode("'{$folderId}' in parents and mimeType='video/mp4' and trashed=false");
        $repoFiles = Http::withToken($token)->get("https://www.googleapis.com/drive/v3/files?q={$query}&fields=files(id,name)");

        if ($repoFiles->successful()) {
            $files = $repoFiles->json()['files'] ?? [];
            
            if (empty($files)) {
                return redirect('/search')->with('error', 'Folder terhubung, tetapi tidak ditemukan video (.mp4) di dalamnya.');
            }

            // Hapus data lama folder ini jika pernah dimasukkan sebelumnya agar tidak duplikat
            VideoMetadata::where('folder_id', $folderId)->delete();

            // Simpan sebagai Aset Mentah (Raw Videos) ke database MySQL (Spasi sudah steril)
            foreach ($files as $file) {
                VideoMetadata::create([
                    'folder_id' => $folderId,
                    'folder_name' => $folderName,
                    'file_id' => $file['id'],
                    'video' => $file['name'],
                    'timestamp' => null,
                    'description' => null,
                ]);
            }

            $count = count($files);
            return redirect('/search')->with('success', "📁 Workspace '{$folderName}' Berhasil Diimpor! {$count} video siap dianalisis.");
        }

        return redirect('/search')->with('error', 'Gagal accessing folder Google Drive. Pastikan ID Folder benar.');
    }

    public function streamVideo($fileId)
    {
        $token = session('google_token');
        if (!$token) {
            return abort(403, 'Unauthorized: Token Google tidak ditemukan.');
        }
        $url = "https://www.googleapis.com/drive/v3/files/{$fileId}?alt=media";
        return redirect($url . "&access_token=" . $token);
    }
}