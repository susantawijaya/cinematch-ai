<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use App\Models\VideoMetadata;
use App\Models\ChatMessage;

class AnalyzeVideoPrompt implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; 
    public $tries = 1;      

    protected $folderId;
    protected $userPrompt;
    protected $accessToken;
    protected $subFolderName;

    public function __construct($folderId, $userPrompt, $accessToken, $subFolderName)
    {
        $this->folderId = $folderId;
        $this->userPrompt = $userPrompt;
        $this->accessToken = $accessToken;
        $this->subFolderName = $subFolderName;
    }

    public function handle(): void
    {
        $scriptPath = base_path('scripts/cloud_indexer.py');
        
        $env = getenv(); 
        $env['SystemRoot'] = 'C:\WINDOWS';
        $env['PYTHONUNBUFFERED'] = '1';
        $env['PYTHONIOENCODING'] = 'utf-8';
        
        $process = new Process(['python', $scriptPath, $this->folderId, $this->userPrompt, $this->accessToken], null, $env);
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
                    $aiReply = "🔒 Akses Google Drive Ditolak\nSistem keamanan Synkora kehilangan izin otorisasi untuk membaca folder Drive ini.\n\n🛠 Solusi: Silakan login dan hubungkan kembali akun Google kamu.";
                } else {
                    $aiReply = "❌ Error Python Asli Terdeteksi\nTerminal mesin merespon dengan:\n" . substr($actualError, 0, 800) . "\n\n🛠 Solusi: Silakan periksa log server.";
                }
                
                ChatMessage::create(['folder_id' => $this->folderId, 'sender' => 'ai', 'message' => $aiReply]);
                return;
            }

            $newResults = $resultData['results'] ?? [];
            $filterStats = $resultData['stats'] ?? ['filtered' => 0, 'analyzed' => 0];
            
            if (!empty($newResults)) {
                $existing = VideoMetadata::where('folder_id', $this->folderId)->first();
                $folderName = $existing ? $existing->folder_name : 'Analyzed Workspace';

                foreach ($newResults as $momen) {
                    VideoMetadata::create([
                        'folder_id'         => $this->folderId,
                        'folder_name'       => $folderName,
                        'sub_folder_name'   => $this->subFolderName,
                        'file_id'           => $momen['file_id'],
                        'video'             => $momen['video'],
                        'timestamp'         => $momen['timestamp'],
                        'timestamp_seconds' => $momen['timestamp_seconds'] ?? 0,
                        'description'       => $momen['description'],
                        'vibe_score'        => $momen['vibe_score'] ?? null,
                        'semantic_tags'     => $momen['semantic_tags'] ?? [],
                    ]);
                }
                
                if ($filterStats['filtered'] > 0) {
                    $aiReply = "Analisis selesai. The Smart Filter telah membuang {$filterStats['filtered']} video buruk. Dari sisa {$filterStats['analyzed']} video yang dianalisis, saya berhasil mengekstrak " . count($newResults) . " momen penting dengan Vibe Score ke sub-folder: **\"{$this->subFolderName}\"**.";
                } else {
                    $aiReply = "Saya telah selesai memindai {$filterStats['analyzed']} rekaman dan berhasil mengekstrak " . count($newResults) . " momen penting (dilengkapi Semantic Tags) ke sub-folder: **\"{$this->subFolderName}\"**.";
                }
            } else {
                if ($filterStats['filtered'] > 0) {
                    $aiReply = "Maaf, Santa. Analisis selesai. Filter membuang {$filterStats['filtered']} video buruk, dan dari sisa {$filterStats['analyzed']} video, tidak ditemukan momen \"{$this->userPrompt}\" sama sekali.";
                } else {
                    $aiReply = "Maaf, Santa. Setelah memindai seluruh {$filterStats['analyzed']} footage, saya tidak menemukan visual yang cocok dengan instruksi: \"{$this->userPrompt}\".";
                }
            }

            ChatMessage::create(['folder_id' => $this->folderId, 'sender' => 'ai', 'message' => $aiReply]);

        } catch (ProcessFailedException $e) {
            $errOutput = $e->getProcess()->getErrorOutput();
            $aiReply = "🛑 Sistem Python Mengalami Crash Fatal\nDetail:\n" . substr($errOutput, 0, 500);
            ChatMessage::create(['folder_id' => $this->folderId, 'sender' => 'ai', 'message' => $aiReply]);
        }
    }
}