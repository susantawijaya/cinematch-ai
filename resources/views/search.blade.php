<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineMatch AI - Smart Search</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white font-sans min-h-screen p-8">

    <div class="max-w-4xl mx-auto">
        <div class="text-center mb-10">
            <h1 class="text-4xl font-bold bg-gradient-to-r from-purple-400 to-pink-600 bg-clip-text text-transparent">CineMatch AI</h1>
            <p class="text-gray-400 mt-2">Cari momen epik dari ribuan video dalam hitungan milidetik.</p>
        </div>

        <form action="/search" method="GET" class="mb-12 relative">
            <input type="text" name="q" value="{{ $query ?? '' }}" placeholder="Cth: jump shot jersey putih merah..." 
                   class="w-full bg-gray-800 border border-gray-700 text-white rounded-full py-4 px-6 text-lg focus:outline-none focus:ring-2 focus:ring-purple-500 shadow-lg transition">
            <button type="submit" class="absolute right-2 top-2 bg-purple-600 hover:bg-purple-700 text-white rounded-full px-6 py-2 text-lg font-medium transition">
                Cari
            </button>
        </form>

        @if(session('success'))
            <div class="bg-green-900 border border-green-700 text-green-200 px-4 py-3 rounded relative mb-8" role="alert">
                <span class="block sm:inline">✅ {{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-900 border border-red-700 text-red-200 px-4 py-3 rounded relative mb-8" role="alert">
                <span class="block sm:inline">❌ {{ session('error') }}</span>
            </div>
        @endif

        <div class="mb-12 bg-gray-800 border border-gray-700 p-6 rounded-xl shadow-md">
            @if(session('google_token'))
                <h3 class="text-xl font-semibold text-purple-400 mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path></svg>
                    Index Video dari Google Drive (Cloud)
                </h3>
                
                <form action="/google-drive/index" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-gray-300 font-medium mb-2">ID Folder Google Drive:</label>
                        <input type="text" name="folder_id" placeholder="Contoh: 1BxiMVs0XRYH..." required 
                               class="w-full bg-gray-900 border border-gray-600 text-white rounded-lg py-2 px-4 focus:outline-none focus:border-purple-500">
                        <p class="text-gray-500 text-sm mt-1">Ambil dari link URL folder Drive milikmu.</p>
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-300 font-medium mb-2 flex items-center justify-between">
                            Instruksi untuk AI (Prompt):
                            <span class="text-xs font-normal text-gray-400 cursor-help" title="Tips: Sebutkan objek, warna, aksi, atau suasana secara spesifik.">ℹ️ Panduan Prompt</span>
                        </label>
                        <textarea name="prompt" rows="3" placeholder="Contoh: Cari momen saat SMAN 1 Bululawang mencetak angka, selebrasi penonton, atau saat pemain berbaju biru jatuh..." required 
                                  class="w-full bg-gray-900 border border-gray-600 text-white rounded-lg py-2 px-4 focus:outline-none focus:border-purple-500"></textarea>
                    </div>

                    <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold py-3 px-4 rounded-lg transition duration-300 shadow-lg flex justify-center items-center">
                        <svg class="w-5 h-5 mr-2 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        Mulai Analisis Cloud
                    </button>
                </form>
            @else
                <div class="text-center">
                    <p class="text-gray-300 mb-4">Ingin mengindex video langsung dari cloud tanpa membebani laptop?</p>
                    <a href="{{ route('google.connect') }}" class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-full transition duration-300">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M21.35 11.1h-9.17v2.73h6.51c-.33 3.81-3.5 5.44-6.5 5.44C8.36 19.27 5 16.25 5 12c0-4.1 3.2-7.27 7.2-7.27 3.09 0 4.9 1.97 4.9 1.97L19 4.72S16.56 2 12.1 2C6.42 2 2.03 6.8 2.03 12c0 5.05 4.13 10 10.22 10 5.35 0 9.25-3.67 9.25-9.09 0-1.15-.15-1.81-.15-1.81z"/></svg>
                        Hubungkan ke Google Drive
                    </a>
                </div>
            @endif
        </div>

        @if(isset($query))
            <h2 class="text-xl text-gray-300 mb-6 border-b border-gray-700 pb-2">
                Hasil pencarian untuk: <span class="text-purple-400 font-semibold">"{{ $query }}"</span> 
                ({{ count($results) }} ditemukan)
            </h2>

            @if(count($results) > 0)
                <div class="space-y-6">
                    @foreach($results as $res)
                        <div class="bg-gray-800 border border-gray-700 p-6 rounded-xl shadow-md hover:border-purple-500 transition duration-300">
                            <div class="flex items-center justify-between mb-3">
                                <span class="bg-purple-900 text-purple-200 text-sm font-bold px-3 py-1 rounded-full">
                                    🎥 {{ $res['video'] }}
                                </span>
                                <span class="bg-pink-900 text-pink-200 text-sm font-bold px-3 py-1 rounded-full">
                                    ⏱️ {{ $res['timestamp'] }}
                                </span>
                            </div>
                            <p class="text-gray-300 leading-relaxed text-sm">
                                {!! preg_replace('/('.preg_quote($query, '/').')/i', '<span class="bg-yellow-500 text-black px-1 rounded font-bold">$1</span>', $res['description']) !!}
                            </p>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center p-10 bg-gray-800 rounded-xl border border-gray-700">
                    <p class="text-gray-400 text-lg">Momen yang kamu cari tidak ditemukan di database.</p>
                </div>
            @endif
        @endif

    </div>
</body>
</html>