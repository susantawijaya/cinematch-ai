<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineMatch AI - JuaraVibeCoding</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .video-container video { width: 100%; border-radius: 16px; background: #000; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3); }
        .hero-gradient { background: linear-gradient(135deg, #4338ca 0%, #7e22ce 100%); }
        .glass { background: rgba(31, 41, 55, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(75, 85, 99, 0.3); }
    </style>
</head>
<body class="bg-[#0b0f1a] text-white font-sans min-h-screen">

    <div class="max-w-6xl mx-auto p-8">
        
        <div class="text-center mb-10">
            <h1 class="text-6xl font-black bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-500 bg-clip-text text-transparent mb-2 italic">
                CineMatch AI
            </h1>
            <div class="h-1 w-24 bg-indigo-500 mx-auto rounded-full mb-4"></div>
            <p class="text-gray-500 tracking-[0.3em] uppercase text-[10px] font-black">Professional Video Intelligence</p>
        </div>

        @if(session('error'))
            <div class="mb-8 p-6 bg-red-950/40 border-l-4 border-red-500 rounded-2xl glass animate-pulse">
                <div class="flex items-start">
                    <div class="bg-red-500/20 p-2 rounded-lg mr-4">
                        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-red-400 font-bold uppercase text-xs tracking-widest">Gangguan Sistem Terdeteksi</h3>
                        <p class="text-red-100 text-sm mt-1">
                            @if(str_contains(session('error'), '429'))
                                <strong>Batas Kuota Gemini Tercapai:</strong> Google membatasi permintaan kamu saat ini. Silakan tunggu 1-2 menit agar jatah API kamu kembali normal.
                            @else
                                {{ session('error') }}
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        @endif

        @if(session('success'))
            <div class="mb-8 p-4 bg-emerald-900/30 border border-emerald-500/50 text-emerald-400 rounded-xl text-sm font-bold text-center">
                ✨ {{ session('success') }}
            </div>
        @endif

        @if(!session('google_token'))
            <div class="hero-gradient p-16 rounded-[40px] shadow-2xl text-center my-10 border border-white/10">
                <h2 class="text-4xl font-extrabold mb-6">Siap Menemukan Momen Juara?</h2>
                <p class="text-indigo-100 mb-10 max-w-xl mx-auto leading-relaxed">
                    Hubungkan akun Google Drive Universitas Ma Chung kamu untuk menganalisis video dokumentasi secara instan dengan Multimodal AI.
                </p>
                <a href="{{ route('google.connect') }}" class="inline-flex items-center bg-white text-indigo-700 hover:bg-indigo-50 font-black px-12 py-5 rounded-full transition-all transform hover:scale-105 shadow-[0_20px_50px_rgba(0,0,0,0.3)]">
                    HUBUNGKAN DRIVE SEKARANG
                </a>
            </div>
        @else
            <form action="/search" method="GET" class="mb-12 relative">
                <input type="text" name="q" value="{{ $query ?? '' }}" placeholder="Cari momen spesifik..." 
                       class="w-full bg-gray-800/50 border border-gray-700 text-white rounded-full py-6 px-10 text-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 glass transition-all">
                <button type="submit" class="absolute right-4 top-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full px-10 py-3 font-black shadow-lg transition">
                    CARI
                </button>
            </form>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
                <div class="lg:col-span-2 bg-gray-800/30 p-8 rounded-3xl border border-gray-700/50 glass">
                    <form action="/google-drive/index" method="POST" class="space-y-4">
                        @csrf
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-black text-indigo-400 uppercase tracking-widest">Analisis Folder Baru</h3>
                            <span class="text-[10px] text-gray-500 uppercase">Gemini 1.5 Flash Enabled</span>
                        </div>
                        <input type="text" name="folder_id" placeholder="Google Drive Folder ID" required 
                               class="w-full bg-gray-900/50 border border-gray-700 text-white rounded-2xl py-4 px-6 focus:border-indigo-500 outline-none transition">
                        <textarea name="prompt" rows="2" placeholder="Instruksi AI (Apa yang harus dicari?)" required 
                                  class="w-full bg-gray-900/50 border border-gray-700 text-white rounded-2xl py-4 px-6 focus:border-indigo-500 outline-none transition"></textarea>
                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-5 rounded-2xl shadow-xl tracking-widest transition active:scale-95">
                            PROSES DENGAN CLOUD AI
                        </button>
                    </form>
                </div>
                <div class="bg-indigo-900/20 p-8 rounded-3xl border border-indigo-500/20 flex flex-col justify-center glass">
                    <h4 class="text-[10px] font-black text-indigo-300 mb-4 uppercase tracking-[0.2em]">Prompt Engineering Tips</h4>
                    <p class="text-xs text-indigo-200/70 leading-relaxed italic">
                        "Cari momen shooting basket dari sisi kiri lapangan, atau momen selebrasi penonton saat bola masuk."
                    </p>
                </div>
            </div>

            @if(isset($query) && $query != '')
                <div class="grid grid-cols-1 gap-12">
                    @foreach($results as $res)
                        <div class="flex flex-col lg:flex-row gap-10 group">
                            <div class="w-full lg:w-3/5 video-container relative">
                                @if(!empty($res['file_id']))
                                    <video id="vid-{{ $loop->index }}" controls preload="metadata">
                                        <source src="{{ route('video.stream', ['fileId' => $res['file_id']]) }}#t={{ $res['timestamp_seconds'] ?? 0 }}" type="video/mp4">
                                    </video>
                                @else
                                    <div class="bg-gray-900 h-64 rounded-2xl flex items-center justify-center border border-dashed border-gray-700">
                                        <p class="text-gray-600 text-xs uppercase tracking-widest">Preview Not Available</p>
                                    </div>
                                @endif
                            </div>
                            <div class="w-full lg:w-2/5 flex flex-col justify-between py-4">
                                <div>
                                    <div class="flex items-center gap-3 mb-6">
                                        <span class="bg-indigo-500 text-white text-[10px] font-black px-4 py-2 rounded-full shadow-lg shadow-indigo-500/20 uppercase">
                                            {{ $res['timestamp'] }}
                                        </span>
                                        <span class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">{{ $res['video'] }}</span>
                                    </div>
                                    <p class="text-gray-300 text-lg leading-relaxed font-medium">
                                        {!! preg_replace('/('.preg_quote($query, '/').')/i', '<span class="bg-indigo-500/30 text-indigo-200 border-b-2 border-indigo-500 px-1 font-bold">$1</span>', $res['description']) !!}
                                    </p>
                                </div>
                                <div class="mt-10 flex gap-4">
                                    <button onclick="exportEDL('{{ $res['video'] }}', '{{ $res['timestamp'] }}')" class="flex-1 bg-gray-800 hover:bg-indigo-600 border border-gray-700 text-[10px] font-black py-4 rounded-2xl transition uppercase tracking-widest">
                                        Export EDL
                                    </button>
                                    <button onclick="exportSRT('{{ $res['timestamp'] }}', '{{ $res['description'] }}')" class="flex-1 bg-gray-800 hover:bg-pink-600 border border-gray-700 text-[10px] font-black py-4 rounded-2xl transition uppercase tracking-widest">
                                        Export SRT
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="h-px w-full bg-gradient-to-r from-transparent via-gray-800 to-transparent"></div>
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
            downloadFile(edlContent, filename.replace('.mp4','') + '.edl');
        }
        function exportSRT(timestamp, desc) {
            const srtContent = `1\n00:00:${timestamp},000 --> 00:00:${timestamp},500\n${desc}`;
            downloadFile(srtContent, 'subtitle.srt');
        }
        function downloadFile(content, fileName) {
            const blob = new Blob([content], { type: 'text/plain' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob); a.download = fileName; a.click();
        }
    </script>
</body>
</html>