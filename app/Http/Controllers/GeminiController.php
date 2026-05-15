<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GeminiController extends Controller
{
    public function index()
    {
        // Berikan nilai default agar Blade tidak marah
        $results = [];
        $query = null;

        // Jika belum ada token, Laravel akan tetap menampilkan halaman search
        // tapi nanti di Blade kita buatkan tampilan "Welcome" yang besar.
        return view('search', compact('results', 'query'));
    }
}