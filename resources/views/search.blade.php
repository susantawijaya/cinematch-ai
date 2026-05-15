<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineMatch AI - JuaraVibeCoding</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .video-container video { width: 100%; border-radius: 12px; background: #000; }
        .hero-gradient { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    </style>
</head>
<body class="bg-gray-900 text-white font-sans min-h-screen">

    <div class="max-w-6xl mx-auto p-8">
        
        <div class="text-center mb-10">
            <h1 class="text-5xl font-black bg-gradient-to-r from-purple-400 to-pink-500 bg-clip-text text-transparent mb-2">
                CineMatch AI
            </h1>
            <p class="text-gray-400 tracking-widest uppercase text-xs font-bold">Smart Video Discovery for PDD Team</p>
        </div>

        @if(!session('google_token'))
            <div class="hero-gradient p-12 rounded-3xl shadow-2xl text-center my-20 animate-fade-in">
                <h2 class="text-3xl font-bold mb-4">Selamat Datang di CineMatch AI!</h2>
                <p class="text-purple-100 mb-8 max-w-lg mx-auto">
                    Hubungkan akun Google Drive kamu untuk mulai menganalisis video dokumentasi menggunakan kecerdasan Gemini AI.
                </p>
                <a href="{{ route('google.connect') }}" class="inline-flex items-center bg-white text-purple-700 hover:bg-gray-100 font-black px-10 py-4 rounded-full transition-all transform hover:scale-105 shadow-xl">
                    <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 24 24"><path d="M21.35 11.1h-9.17v2.73h6.51c-.33 3.81-3.5 5.44-6.5 5.44C8.36 19.27 5 16.25 5 12c0-4.1 3.2-7.27 7.2-7.27 3.09 0 4.9 1.97 4.9 1.97L19 4.72S16.56 2 12.1 2C6.42 2 2.03 6.8 2.03 12c0 5.05 4.13 10 10.22 10 5.35 0 9.25-3.67 9.25-9.09 0-1.15-.15-1.81-.15-1.81z"/></svg>
                    HUBUNGKAN GOOGLE DRIVE SEKARANG
                </a>
            </div>
        @else
            <form action="/search" method="GET" class="mb-12 relative group">
                <input type="text" name="q" value="{{ $query ?? '' }}" placeholder="Cari momen (cth: 'shooting basket', 'audiens tertawa')..." 
                       class="w-full bg-gray-800 border border-gray-700 text-white rounded-full py-5 px-8 text-xl focus:outline-none focus:ring-2 focus:ring-purple-500 shadow-2xl transition-all">
                <button type="submit" class="absolute right-3 top-3 bg-purple-600 hover:bg-purple-700 text-white rounded-full px-8 py-3 text-lg font-bold shadow-lg">
                    Cari
                </button>
            </form>

            <div class="mb-12 bg-gray-800/50 border border-gray-700 p-8 rounded-3xl shadow-xl">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                    <form action="/google-drive/index" method="POST" class="space-y-4">
                        @csrf
                        <h3 class="text-xl font-bold text-purple-400 mb-2">Cloud Analysis</h3>
                        <input type="text" name="folder_id" placeholder="ID Folder Google Drive" required 
                               class="w-full bg-gray-900 border border-gray-700 text-white rounded-xl py-3 px-4 focus:border-purple-500 outline-none transition">
                        <textarea name="prompt" rows="2" placeholder="Apa yang AI harus cari?" required 
                                  class="w-full bg-gray-900 border border-gray-700 text-white rounded-xl py-3 px-4 focus:border-purple-500 outline-none transition"></textarea>
                        <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white font-black py-4 rounded-xl shadow-lg">
                            MULAI ANALISIS GEMINI
                        </button>
                    </form>
                    <div class="bg-gray-900/80 p-6 rounded-2xl border border-gray-700/50 flex flex-col justify-center">
                        <h4 class="text-xs font-black text-gray-500 mb-4 uppercase">Tips Prompting</h4>
                        <p class="text-xs text-gray-400 leading-relaxed italic">
                            "Cari momen saat SMAN 1 Bululawang mencetak angka, selebrasi penonton, atau saat pemain berbaju biru jatuh..."
                        </p>
                    </div>
                </div>
            </div>

            @if(isset($query) && $query != '')
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-2xl font-bold text-gray-200">Hasil: <span class="text-purple-400">"{{ $query }}"</span></h2>
                    <span class="text-gray-500 text-sm font-mono">{{ count($results) }} Momen</span>
                </div>

                <div class="grid grid-cols-1 gap-10">
                    @foreach($results as $res)
                        <div class="bg-gray-800 border border-gray-700 p-8 rounded-3xl shadow-2xl flex flex-col lg:flex-row gap-8">
                            <div class="w-full lg:w-1/2 video-container">
                                @if(!empty($res['file_id']))
                                    <video id="vid-{{ $loop->index }}" controls preload="metadata">
                                        <source src="{{ route('video.stream', $res['file_id']) }}#t={{ $res['timestamp_seconds'] ?? 0 }}" type="video/mp4">
                                    </video>
                                @endif
                            </div>
                            <div class="w-full lg:w-1/2 flex flex-col justify-between">
                                <div>
                                    <span class="bg-pink-500/10 text-pink-400 text-xs font-black px-4 py-2 rounded-lg border border-pink-500/20">⏱️ {{ $res['timestamp'] }}</span>
                                    <p class="text-gray-300 text-base mt-6 leading-relaxed">
                                        {!! preg_replace('/('.preg_quote($query, '/').')/i', '<span class="bg-yellow-500/30 text-yellow-200 border-b-2 border-yellow-500 px-1 font-bold">$1</span>', $res['description']) !!}
                                    </p>
                                </div>
                                <div class="mt-8 flex gap-3">
                                    <button onclick="exportEDL('{{ $res['video'] }}', '{{ $res['timestamp'] }}')" class="flex-1 bg-gray-700 hover:bg-blue-600 text-[10px] font-bold py-3 rounded-xl transition">💻 PREMIERE</button>
                                    <button onclick="exportSRT('{{ $res['timestamp'] }}', '{{ $res['description'] }}')" class="flex-1 bg-gray-700 hover:bg-pink-600 text-[10px] font-bold py-3 rounded-xl transition">📱 CAPCUT</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>

    <script>
        function exportEDL(filename, timestamp) {
            const timeParts = timestamp.split(':');
            const seconds = parseInt(timeParts[0]) * 60 + parseInt(timeParts[1]);
            const startTime = new Date(seconds * 1000).toISOString().substr(11, 8) + ":00";
            const endTime = new Date((seconds + 5) * 1000).toISOString().substr(11, 8) + ":00";
            const edlContent = `TITLE: CINEMATCH\nFCM: NON-DROP FRAME\n\n001 AX V C ${startTime} ${endTime} ${startTime} ${endTime}\n* FROM CLIP NAME: ${filename}`;
            downloadFile(edlContent, 'export.edl');
        }
        function exportSRT(timestamp, desc) {
            const srtContent = `1\n00:00:${timestamp},000 --> 00:00:${timestamp},500\n${desc}`;
            downloadFile(srtContent, 'subtitle.srt');
        }
        function downloadFile(content, fileName) {
            const blob = new Blob([content], { type: 'text/plain' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = fileName;
            a.click();
        }
    </script>
</body>
</html>