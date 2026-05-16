<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

use App\Http\Controllers\GoogleDriveController;
use App\Models\VideoMetadata; 
use App\Models\ChatMessage;

Route::get('/', function () { return view('welcome'); });

// --- GOOGLE DRIVE CORE ---
Route::get('/google-drive/connect', [GoogleDriveController::class, 'redirectToGoogle'])->name('google.connect');
Route::get('/google-drive/callback', [GoogleDriveController::class, 'handleGoogleCallback']);
Route::get('/video-stream/{fileId}', [GoogleDriveController::class, 'streamVideo'])->name('video.stream');
Route::post('/google-drive/connect-folder', [GoogleDriveController::class, 'connectFolder'])->name('google.connectFolder');
Route::post('/google-drive/sync-folder', [GoogleDriveController::class, 'connectFolder'])->name('google.syncFolder'); 
Route::post('/google-drive/delete-folder', [GoogleDriveController::class, 'deleteFolder'])->name('google.deleteFolder');

// --- LOGOUT SYSTEM ---
Route::post('/logout', function () {
    session()->forget('google_token');
    return redirect('/');
})->name('logout');

// --- FULL AJAX MANAGEMENT SUB-FOLDER & CLIPS ---
Route::post('/google-drive/rename-subfolder', function (Request $request) {
    VideoMetadata::where('folder_id', $request->folder_id)
        ->where('sub_folder_name', $request->old_name)
        ->update(['sub_folder_name' => $request->new_name]);
        
    return response()->json([
        'status' => 'success',
        'all_assets' => VideoMetadata::all()->toArray()
    ]);
});

Route::post('/google-drive/delete-subfolder', function (Request $request) {
    VideoMetadata::where('folder_id', $request->folder_id)
        ->where('sub_folder_name', $request->sub_folder_name)
        ->delete();
        
    return response()->json([
        'status' => 'success',
        'all_assets' => VideoMetadata::all()->toArray()
    ]);
});

Route::post('/google-drive/delete-clip', function (Request $request) {
    VideoMetadata::where('id', $request->clip_id)->delete();
    
    return response()->json([
        'status' => 'success',
        'all_assets' => VideoMetadata::all()->toArray()
    ]);
});

// --- 🔥 FITUR BARU: HAPUS HISTORI OBROLAN ---
Route::post('/chat/clear', function (Request $request) {
    ChatMessage::where('folder_id', $request->folder_id)->delete();
    return response()->json(['status' => 'success']);
});

// --- WORKSPACE SEARCH ISOLATION HUB ---
Route::get('/search', function (Request $request) {
    $query = $request->input('q');
    $folderFilter = $request->input('folder_filter', 'all'); 
    
    $allAssets = VideoMetadata::all();
    $chatMessages = ChatMessage::where('folder_id', $folderFilter)->get();
    $results = collect();

    if ($folderFilter !== 'all') {
        $dbQuery = VideoMetadata::where('folder_id', $folderFilter);
        if ($query) {
            $dbQuery->where('description', 'LIKE', '%' . $query . '%');
        }
        $results = $dbQuery->get();
    }

    return view('search', [
        'query' => $query,
        'results' => $results->toArray(),
        'all_assets' => $allAssets->toArray(),
        'chat_messages' => $chatMessages->toArray()
    ]);
});

// --- CHAT AI PROMPT STUDIO ---
Route::post('/google-drive/index', function (Request $request) {
    set_time_limit(0);
    $folderId = $request->input('folder_id');
    $userPrompt = $request->input('prompt');
    $accessToken = session('google_token'); 

    if (!$accessToken) {
        return response()->json(['status' => 'error', 'message' => 'Token Google tidak ditemukan.'], 401);
    }

    ChatMessage::create(['folder_id' => $folderId, 'sender' => 'user', 'message' => $userPrompt]);

    $fillers = ['carikan', 'saya', 'momen', 'video', 'yang', 'sedang', 'di', 'ke', 'tolong', 'ada', 'seorang', 'pemain'];
    $cleanWords = str_replace($fillers, '', strtolower($userPrompt));
    $subFolderName = ucwords(trim(preg_replace('/\s+/', ' ', $cleanWords)));
    if (empty($subFolderName)) { $subFolderName = 'Ekstraksi Momen ' . date('H:i'); }

    $scriptPath = base_path('scripts/cloud_indexer.py');
    
    $env = getenv(); 
    $env['SystemRoot'] = 'C:\WINDOWS';
    $env['PYTHONUNBUFFERED'] = '1';
    $env['PYTHONIOENCODING'] = 'utf-8';
    
    $process = new Process(['python', $scriptPath, $folderId, $userPrompt, $accessToken], null, $env);
    $process->setTimeout(3600); 

    try {
        $process->mustRun();
        
        $rawOutput = trim($process->getOutput());
        $errOutput = trim($process->getErrorOutput());
        
        $jsonStart = strpos($rawOutput, '{');
        $jsonEnd = strrpos($rawOutput, '}');
        
        if ($jsonStart !== false && $jsonEnd !== false) {
            $cleanJson = substr($rawOutput, $jsonStart, $jsonEnd - $jsonStart + 1);
            $resultData = json_decode($cleanJson, true);
        } else {
            $resultData = null; 
        }

        if (is_null($resultData) || isset($resultData['error'])) {
            $actualError = isset($resultData['error']) ? $resultData['error'] : ($errOutput ?: $rawOutput);
            if (empty($actualError)) $actualError = "Terminal Python terdiam dan tidak memberikan output.";
            
            $rawError = strtolower($actualError);
            
            if (str_contains($rawError, '429') || str_contains($rawError, 'quota') || str_contains($rawError, 'exhausted')) {
                $aiReply = "🚨 Limit Kuota API Tercapai (Error 429)\nGoogle Gemini mendeteksi bahwa kamu telah mencapai batas maksimal permintaan token gratis untuk menit ini.\n\n🛠 Solusi: Mohon tunggu sekitar 2-3 menit sebelum menekan tombol Send lagi.";
            } elseif (str_contains($rawError, 'safety filter') || str_contains($rawError, 'menolak merespon')) {
                $aiReply = "🛡️ Diblokir oleh Filter Keamanan Gemini\nSistem AI menolak memproses frame video ini karena mendeteksi visual yang dilarang.\n\n🛠 Solusi: Analisis rekaman video lain yang lebih aman, atau ganti instruksi pencarianmu.";
            } elseif (str_contains($rawError, '400') || str_contains($rawError, 'invalid')) {
                $aiReply = "⚠️ Permintaan Tidak Valid (Error 400)\nGoogle Gemini menolak memproses instruksi ini, kemungkinan karena format file video tidak didukung atau rusak.\n\n🛠 Solusi: Pastikan semua video di folder ini bisa diputar secara normal.";
            } elseif (str_contains($rawError, '500') || str_contains($rawError, '503') || str_contains($rawError, 'unavailable')) {
                $aiReply = "🌐 Server Google AI Sedang Sibuk (Error 50x)\nServer AI Google saat ini sedang kelebihan beban (overload) atau mengalami gangguan jaringan.\n\n🛠 Solusi: Ini murni dari server Google. Tunggu beberapa saat dan coba kirim pesanmu kembali.";
            } elseif (str_contains($rawError, 'drive') || str_contains($rawError, 'credentials') || str_contains($rawError, 'unauthorized')) {
                $aiReply = "🔒 Akses Google Drive Ditolak\nSistem keamanan Synkora kehilangan izin otorisasi untuk membaca folder Drive ini.\n\n🛠 Solusi: Silakan klik tombol 'Logout Account' di kanan atas layar, lalu login dan hubungkan kembali akun Google kamu.";
            } else {
                $aiReply = "❌ Error Python Asli Terdeteksi\nTerminal mesin merespon dengan:\n" . substr($actualError, 0, 800) . "\n\n🛠 Solusi: Silakan beritahu pesan bahasa Inggris di atas kepada saya agar kita tahu penyakit pastinya!";
            }
            
            ChatMessage::create(['folder_id' => $folderId, 'sender' => 'ai', 'message' => $aiReply]);
            
            return response()->json([
                'status' => 'error',
                'message' => $aiReply,
                'chat_messages' => ChatMessage::where('folder_id', $folderId)->get()->toArray(),
                'all_assets' => VideoMetadata::all()->toArray()
            ]);
        }

        $newResults = $resultData['results'] ?? [];
        
        if (!empty($newResults)) {
            $existing = VideoMetadata::where('folder_id', $folderId)->first();
            $folderName = $existing ? $existing->folder_name : 'Analyzed Workspace';

            foreach ($newResults as $momen) {
                VideoMetadata::create([
                    'folder_id'         => $folderId,
                    'folder_name'       => $folderName,
                    'sub_folder_name'   => $subFolderName,
                    'file_id'           => $momen['file_id'],
                    'video'             => $momen['video'],
                    'timestamp'         => $momen['timestamp'],
                    'timestamp_seconds' => $momen['timestamp_seconds'] ?? 0,
                    'description'       => $momen['description'],
                ]);
            }
            $aiReply = "Saya telah selesai memindai rekaman dan berhasil mengekstrak " . count($newResults) . " momen penting. Hasilnya kini sudah saya amankan ke dalam sub-folder baru: **\"{$subFolderName}\"**.";
        } else {
            $aiReply = "Maaf, Santa. Setelah memindai seluruh footage di workspace ini dengan sangat teliti, saya tidak menemukan visual yang cocok dengan instruksi: \"{$userPrompt}\". Momen tersebut saat ini tidak tersedia di dalam rekaman berkas video.";
        }

        ChatMessage::create(['folder_id' => $folderId, 'sender' => 'ai', 'message' => $aiReply]);

        return response()->json([
            'status' => 'success',
            'message' => $aiReply,
            'chat_messages' => ChatMessage::where('folder_id', $folderId)->get()->toArray(),
            'all_assets' => VideoMetadata::all()->toArray()
        ]);

    } catch (ProcessFailedException $e) {
        $errOutput = $e->getProcess()->getErrorOutput();
        $aiReply = "🛑 Sistem Python Mengalami Crash Fatal\nSistem gagal mengeksekusi mesin AI. Detail:\n" . substr($errOutput, 0, 500) . "\n\n🛠 Solusi: Copy teks error ini ke saya.";
        ChatMessage::create(['folder_id' => $folderId, 'sender' => 'ai', 'message' => $aiReply]);
        
        return response()->json([
            'status' => 'error',
            'message' => $aiReply,
            'chat_messages' => ChatMessage::where('folder_id', $folderId)->get()->toArray(),
            'all_assets' => VideoMetadata::all()->toArray()
        ]);
    }
});