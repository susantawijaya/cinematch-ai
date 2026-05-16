<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Http;
use App\Models\VideoMetadata;
use App\Models\ChatMessage;

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

    // Fungsi Sync yang sekarang mendukung AJAX murni
    public function connectFolder(Request $request)
    {
        $folderId = $request->input('folder_id');
        $token = session('google_token');

        if (!$token) {
            return redirect('/search')->with('error', 'Token Google tidak ditemukan. Silakan hubungkan ulang.');
        }

        $folderName = 'Untitled Workspace';
        $repoFolder = Http::withToken($token)->get("https://www.googleapis.com/drive/v3/files/{$folderId}?fields=name");
        if ($repoFolder->successful()) {
            $folderName = $repoFolder->json()['name'] ?? 'Untitled Workspace';
        }

        $query = urlencode("'{$folderId}' in parents and mimeType='video/mp4' and trashed=false");
        $repoFiles = Http::withToken($token)->get("https://www.googleapis.com/drive/v3/files?q={$query}&fields=files(id,name)");

        if ($repoFiles->successful()) {
            $files = $repoFiles->json()['files'] ?? [];
            
            VideoMetadata::where('folder_id', $folderId)->whereNull('timestamp')->delete();

            foreach ($files as $file) {
                // Cek apakah file mentah sudah ada, agar tidak ganda
                $exists = VideoMetadata::where('folder_id', $folderId)->where('file_id', $file['id'])->whereNull('timestamp')->first();
                if (!$exists) {
                    VideoMetadata::create([
                        'folder_id'   => $folderId,
                        'folder_name' => $folderName,
                        'file_id'     => $file['id'],
                        'video'       => $file['name'],
                        'timestamp'   => null,
                        'description' => null,
                    ]);
                }
            }

            // Jika dipanggil via AJAX (Tombol Sync), kembalikan data JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'all_assets' => VideoMetadata::all()->toArray()
                ]);
            }

            return redirect('/search?folder_filter=' . $folderId)->with('success', "Workspace berhasil diimpor!");
        }

        return redirect('/search')->with('error', 'Gagal mengakses folder Google Drive.');
    }

    // 🔥 FITUR YANG TERLUPAKAN: Fungsi Hapus Workspace Beserta Chat Historinya
    public function deleteFolder(Request $request)
    {
        $folderId = $request->input('folder_id');
        
        // Bersihkan semua video dan chat dari database untuk folder ini
        VideoMetadata::where('folder_id', $folderId)->delete();
        ChatMessage::where('folder_id', $folderId)->delete();

        // Jika dipanggil via AJAX
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success']);
        }

        return redirect('/search?folder_filter=all')->with('success', 'Workspace berhasil dihapus!');
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