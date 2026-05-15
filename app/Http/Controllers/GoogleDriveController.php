<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class GoogleDriveController extends Controller
{
    // Langkah 1: Lempar user ke halaman Login Google
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->scopes(['https://www.googleapis.com/auth/drive.readonly'])
            ->with(['access_type' => 'offline', 'prompt' => 'consent'])
            ->redirect();
    }

    // Langkah 2: Terima token kembali dari Google
    public function handleGoogleCallback()
    {
        try {
            $user = Socialite::driver('google')->user();
            
            // Simpan token ke dalam session agar bisa dipakai nanti
            session(['google_token' => $user->token]);

            return redirect('/search')->with('success', 'Berhasil terhubung ke Google Drive!');
        } catch (\Exception $e) {
            return redirect('/search')->with('error', 'Gagal terhubung: ' . $e->getMessage());
        }
    }
}