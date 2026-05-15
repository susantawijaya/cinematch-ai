<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GeminiController;
use App\Http\Controllers\VideoSearchController;
use App\Http\Controllers\GoogleDriveController;

Route::get('/', [GeminiController::class, 'index']);
Route::post('/analyze', [GeminiController::class, 'analyze'])->name('analyze');

Route::get('/cek-model', function () {
    $apiKey = trim(env('GEMINI_API_KEY'));
    $response = Illuminate\Support\Facades\Http::get("https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}");
    return response()->json($response->json());
});
Route::get('/search', [VideoSearchController::class, 'index']);
Route::get('/google-drive/connect', [GoogleDriveController::class, 'redirectToGoogle'])->name('google.connect');
Route::get('/google-drive/callback', [GoogleDriveController::class, 'handleGoogleCallback']);