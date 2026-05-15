<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

use App\Http\Controllers\GeminiController;
use App\Http\Controllers\VideoSearchController;
use App\Http\Controllers\GoogleDriveController;

Route::get('/', [GeminiController::class, 'index']);
Route::get('/search', [VideoSearchController::class, 'index']);

// --- ROUTE GOOGLE DRIVE ---
Route::get('/google-drive/connect', [GoogleDriveController::class, 'redirectToGoogle'])->name('google.connect');
Route::get('/google-drive/callback', [GoogleDriveController::class, 'handleGoogleCallback']);
Route::get('/video-stream/{fileId}', [GoogleDriveController::class, 'streamVideo'])->name('video.stream');

Route::post('/google-drive/index', function (Request $request) {
    set_time_limit(0);

    $folderId = $request->input('folder_id');
    $userPrompt = $request->input('prompt');
    $accessToken = session('google_token'); 

    if (!$accessToken) {
        return redirect('/')->with('error', 'Token Google tidak ditemukan. Silakan hubungkan ulang.');
    }

    $scriptPath = base_path('scripts/cloud_indexer.py');
    $env = getenv();
    $env['SystemRoot'] = 'C:\WINDOWS';

    $process = new Process(['python', $scriptPath, $folderId, $userPrompt, $accessToken], null, $env);
    $process->setTimeout(3600); 

    try {
        $process->mustRun();
        $output = $process->getOutput();
        
        $resultData = json_decode($output, true);

        if (is_null($resultData)) {
            // Jika Python crash tapi tidak mengirim JSON, kita tangkap output mentahnya
            return redirect('/search')->with('error', 'Gagal memproses video. Detail: ' . substr($output, 0, 200));
        }

        if (isset($resultData['error'])) {
            return redirect('/search')->with('error', $resultData['error']);
        }

        $newResults = $resultData['results'] ?? [];
        if (empty($newResults)) {
            return redirect('/search')->with('error', 'AI tidak menemukan momen yang sesuai di folder tersebut.');
        }

        $indexPath = base_path('video_metadata_index.json');
        $existingData = File::exists($indexPath) ? json_decode(File::get($indexPath), true) : [];
        
        $mergedData = array_merge($existingData, $newResults);
        File::put($indexPath, json_encode($mergedData, JSON_PRETTY_PRINT));

        return redirect('/search')->with('success', '⚡ Analisis selesai! ' . count($newResults) . ' momen baru ditambahkan.');

    } catch (ProcessFailedException $e) {
        // Menangkap error 429 atau error library Python langsung dari terminal
        $errorMsg = $e->getProcess()->getErrorOutput() ?: $e->getMessage();
        return redirect('/search')->with('error', 'System Error: ' . $errorMsg);
    }
});