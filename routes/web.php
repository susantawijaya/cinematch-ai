<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GeminiController;
Route::get('/', [GeminiController::class, 'index']);
Route::post('/analyze', [GeminiController::class, 'analyze'])->name('analyze');

Route::get('/cek-model', function () {
    $apiKey = trim(env('GEMINI_API_KEY'));
    $response = Illuminate\Support\Facades\Http::get("https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}");
    return response()->json($response->json());
});
