<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
class GeminiController extends Controller {
    public function index() { return view('welcome'); }
    public function analyze(Request $request)
    {
        // 1. Validasi gambar
        $request->validate([
            'frames' => 'required|array',
            'frames.*' => 'required|image'
        ]);

        // KITA TAMBAHKAN TRIM() UNTUK MEMBUNUH KARAKTER TERSEMBUNYI DARI .ENV
        $apiKey = trim(env('GEMINI_API_KEY'));
        $model = trim(env('GEMINI_MODEL', 'gemini-1.5-flash'));

        // 2. Siapkan instruksi (Prompt)
        $parts = [
            ['text' => "Kamu adalah asisten video editor. Analisis frame-frame ini dan kembalikan HANYA format JSON murni. Pilih tepat satu frame yang paling sinematik. Berikan field: best_frame_number, best_frame_filename, dan vibe_description."]
        ];

        // 3. Ubah gambar ke format Base64
        foreach ($request->file('frames') as $index => $frame) {
            $parts[] = [
                'text' => sprintf('Frame %d - %s', $index + 1, $frame->getClientOriginalName()),
            ];
            $parts[] = [
                'inlineData' => [
                    'mimeType' => $frame->getMimeType(),
                    'data' => base64_encode(file_get_contents($frame->getRealPath()))
                ]
            ];
        }

        // 4. Kirim ke Google Gemini
        $response = Http::timeout(120)->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
            'contents' => [['parts' => $parts]],
            'generationConfig' => ['responseMimeType' => 'application/json']
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'API Error', 'details' => $response->json()], $response->status());
        }

        // 5. Kembalikan hasil JSON
        $responseData = $response->json();
        $text = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
        
        return response($text)->header('Content-Type', 'application/json');
    }
}
