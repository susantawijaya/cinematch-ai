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

// --- 🔥 FITUR BARU: LOGOUT SYSTEM ---
Route::post('/logout', function () {
    session()->forget('google_token'); // Menghapus token Google dari memori session
    return redirect('/');              // Mengembalikan user ke Landing Page luar
})->name('logout');

// --- MANAGEMENT SUB-FOLDER & CLIPS ---
Route::post('/google-drive/rename-subfolder', function (Request $request) {
    VideoMetadata::where('folder_id', $request->folder_id)
        ->where('sub_folder_name', $request->old_name)
        ->update(['sub_folder_name' => $request->new_name]);
    return redirect('/search?folder_filter=' . $request->folder_id)->with('success', 'Sub-Folder berhasil di-rename!');
});

Route::post('/google-drive/delete-subfolder', function (Request $request) {
    VideoMetadata::where('folder_id', $request->folder_id)
        ->where('sub_folder_name', $request->sub_folder_name)
        ->delete();
    return redirect('/search?folder_filter=' . $request->folder_id)->with('success', 'Sub-Folder beserta isinya berhasil dihapus!');
});

Route::post('/google-drive/delete-clip', function (Request $request) {
    VideoMetadata::where('id', $request->clip_id)->delete();
    return redirect('/search?folder_filter=' . $request->folder_id)->with('success', 'Klip video berhasil dibuang dari sub-folder!');
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

// --- CHAT AI PROMPT STUDIO (CONVERSATIONAL VERSION) ---
Route::post('/google-drive/index', function (Request $request) {
    set_time_limit(0);
    $folderId = $request->input('folder_id');
    $userPrompt = $request->input('prompt');
    $accessToken = session('google_token'); 

    if (!$accessToken) return redirect('/search?folder_filter=' . $folderId)->with('error', 'Token Google tidak ditemukan.');

    ChatMessage::create(['folder_id' => $folderId, 'sender' => 'user', 'message' => $userPrompt]);

    $fillers = ['carikan', 'saya', 'momen', 'video', 'yang', 'sedang', 'di', 'ke', 'tolong', 'ada', 'seorang', 'pemain'];
    $cleanWords = str_replace($fillers, '', strtolower($userPrompt));
    $subFolderName = ucwords(trim(preg_replace('/\s+/', ' ', $cleanWords)));
    if (empty($subFolderName)) { $subFolderName = 'Ekstraksi Momen ' . date('H:i'); }

    $scriptPath = base_path('scripts/cloud_indexer.py');
    $env = getenv(); $env['SystemRoot'] = 'C:\WINDOWS';
    $process = new Process(['python', $scriptPath, $folderId, $userPrompt, $accessToken], null, $env);
    $process->setTimeout(3600); 

    try {
        $process->mustRun();
        $resultData = json_decode($process->getOutput(), true);

        if (is_null($resultData) || isset($resultData['error'])) {
            $err = $resultData['error'] ?? 'Gagal memproses video.';
            ChatMessage::create(['folder_id' => $folderId, 'sender' => 'ai', 'message' => 'Maaf, saya mengalami error: ' . $err]);
            return redirect('/search?folder_filter=' . $folderId)->with('error', 'AI Core Error.');
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
            $aiReply = "Saya berhasil menganalisis seluruh footage dan mengekstrak " . count($newResults) . " momen penting. Potongan klip sudah saya kelompokkan ke dalam sub-folder baru: **\"{$subFolderName}\"**.";
        } else {
            $aiReply = "Saya sudah memeriksa seluruh rekaman video, namun tidak menemukan momen yang sesuai dengan instruksi: \"{$userPrompt}\".";
        }

        ChatMessage::create(['folder_id' => $folderId, 'sender' => 'ai', 'message' => $aiReply]);
        return redirect('/search?folder_filter=' . $folderId)->with('success', '⚡ Obrolan AI berhasil diperbarui!');

    } catch (ProcessFailedException $e) {
        ChatMessage::create(['folder_id' => $folderId, 'sender' => 'ai', 'message' => 'System kegagalan eksekusi skrip Python.']);
        return redirect('/search?folder_filter=' . $folderId)->with('error', 'System Error.');
    }
});