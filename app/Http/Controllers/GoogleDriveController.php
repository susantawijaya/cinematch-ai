<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

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
            // REVISI UTAMA: Tambahkan ->stateless() agar login lebih stabil 
            // dan tidak rewel soal pengecekan 'state' session di browser.
            $user = Socialite::driver('google')->stateless()->user();
            
            session(['google_token' => $user->token]);
            
            return redirect('/search')->with('success', 'Berhasil terhubung ke Google Drive!');
        } catch (\Exception $e) {
            return redirect('/')->with('error', 'Gagal menghubungkan akun Google: ' . $e->getMessage());
        }
    }

    // FUNGSI UNTUK DEPLOYMENT & LOCAL STREAMING
    public function streamVideo($fileId)
    {
        $token = session('google_token');
        
        if (!$token) {
            return abort(403, 'Unauthorized: Token Google tidak ditemukan. Silakan login ulang.');
        }

        // Mengarahkan video player langsung ke sumber data Google Drive
        $url = "https://www.googleapis.com/drive/v3/files/{$fileId}?alt=media";
        
        return redirect($url . "&access_token=" . $token);
    }
}