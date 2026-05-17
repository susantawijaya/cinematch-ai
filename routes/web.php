<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\GoogleDriveController;
use App\Models\VideoMetadata; 
use App\Models\ChatMessage;
use App\Jobs\AnalyzeVideoPrompt;

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

// --- 🔥 FITUR BARU: ROUTE POLLING UNTUK REACT ---
// React akan memanggil rute ini diam-diam setiap 3 detik untuk mengecek apakah Job AI sudah selesai
Route::get('/workspace/status/{folderId}', function ($folderId) {
    return response()->json([
        'chat_messages' => ChatMessage::where('folder_id', $folderId)->get()->toArray(),
        'all_assets' => VideoMetadata::all()->toArray()
    ]);
});

// --- CHAT AI PROMPT STUDIO (DILEMPAR KE QUEUE BACKGROUND) ---
Route::post('/google-drive/index', function (Request $request) {
    $folderId = $request->input('folder_id');
    $userPrompt = $request->input('prompt');
    $accessToken = session('google_token'); 

    if (!$accessToken) {
        return response()->json(['status' => 'error', 'message' => 'Token Google tidak ditemukan.'], 401);
    }

    // 1. Catat pesan user ke database
    ChatMessage::create(['folder_id' => $folderId, 'sender' => 'user', 'message' => $userPrompt]);

    // 2. Siapkan nama sub-folder
    $fillers = ['carikan', 'saya', 'momen', 'video', 'yang', 'sedang', 'di', 'ke', 'tolong', 'ada', 'seorang', 'pemain'];
    $cleanWords = str_replace($fillers, '', strtolower($userPrompt));
    $subFolderName = ucwords(trim(preg_replace('/\s+/', ' ', $cleanWords)));
    if (empty($subFolderName)) { $subFolderName = 'Ekstraksi Momen ' . date('H:i'); }

    // 3. 🔥 EKSEKUSI AJAIB: Lempar beban berat ini ke Pekerja Latar Belakang (Queue)
    AnalyzeVideoPrompt::dispatch($folderId, $userPrompt, $accessToken, $subFolderName);

    // 4. Langsung balas ke React dalam 0.1 detik tanpa membuat browser macet!
    return response()->json([
        'status' => 'processing',
        'message' => 'Pekerjaan telah masuk antrean. Sistem sedang memproses di latar belakang...',
        'chat_messages' => ChatMessage::where('folder_id', $folderId)->get()->toArray(),
    ]);
});