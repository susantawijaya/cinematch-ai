<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineMatch AI - Studio Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass-panel { background: rgba(17, 24, 39, 0.7); backdrop-filter: blur(16px); border: 1px solid rgba(75, 85, 99, 0.4); }
        .glass-sidebar { background: rgba(11, 15, 25, 0.95); border-right: 1px solid rgba(31, 41, 55, 1); }
        .hero-text { background: linear-gradient(to right, #818cf8, #c084fc, #f472b6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="bg-[#050810] text-gray-200 h-screen overflow-hidden flex">

    <aside class="w-72 glass-sidebar flex flex-col justify-between h-full relative z-20">
        <div>
            <div class="h-20 flex items-center px-8 border-b border-gray-800">
                <svg class="w-8 h-8 text-indigo-500 mr-3" fill="currentColor" viewBox="0 0 24 24"><path d="M4 6h16v12H4z" opacity=".2"/><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4V6h16v12zM8 15l8-5-8-5z"/></svg>
                <h1 class="text-2xl font-black italic tracking-tighter text-white">CineMatch<span class="text-indigo-500">.AI</span></h1>
            </div>

            <nav class="p-4 mt-4 space-y-2">
                <p class="px-4 text-[10px] font-black tracking-[0.2em] text-gray-500 mb-2">MAIN STUDIO</p>
                <a href="#" class="flex items-center px-4 py-3 bg-indigo-600/10 text-indigo-400 rounded-xl border border-indigo-500/20 font-bold transition">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    AI Search
                </a>
                <a href="#" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-800/50 rounded-xl font-bold transition">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
                    Workspaces
                </a>
                
                <p class="px-4 text-[10px] font-black tracking-[0.2em] text-gray-500 mb-2 mt-8">CREATIVE TOOLS</p>
                <a href="#" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-800/50 rounded-xl font-bold transition">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Auto-Rough Cut <span class="ml-auto bg-pink-500 text-white text-[8px] px-2 py-1 rounded-full uppercase">Pro</span>
                </a>
                <a href="#" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-800/50 rounded-xl font-bold transition">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path></svg>
                    Beat-Sync
                </a>
            </nav>
        </div>

        <div>
            <div class="m-4 p-4 bg-gray-900 rounded-2xl border border-gray-800">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-xs font-bold text-gray-400 uppercase">API Quota</span>
                    <span class="text-xs font-bold text-emerald-400">Stable</span>
                </div>
                <div class="w-full bg-gray-800 rounded-full h-1.5 mb-1">
                    <div class="bg-indigo-500 h-1.5 rounded-full" style="width: 15%"></div>
                </div>
                <p class="text-[10px] text-gray-500 mt-2">1,500 requests remaining today</p>
            </div>

            <div class="p-4 border-t border-gray-800 bg-gray-900/30">
                @if(session('google_token'))
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold shadow-lg">
                            PDD
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-bold text-white leading-tight">PDD Team</p>
                            <p class="text-[10px] text-emerald-400">Connected to Google Drive</p>
                        </div>
                    </div>
                @else
                    <a href="{{ route('google.connect') }}" class="w-full flex items-center justify-center bg-white text-gray-900 hover:bg-gray-100 font-bold py-3 rounded-xl transition">
                        <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24"><path fill="currentColor" d="M21.35,11.1H12.18V13.83H18.69C18.36,17.64 15.19,19.27 12.19,19.27C8.36,19.27 5,16.25 5,12C5,7.9 8.2,4.73 12.2,4.73C15.29,4.73 17.1,6.7 17.1,6.7L19,4.72C19,4.72 16.56,2 12.1,2C6.42,2 2.03,6.8 2.03,12C2.03,17.05 6.16,22 12.25,22C17.6,22 21.5,18.33 21.5,12.91C21.5,11.76 21.35,11.1 21.35,11.1V11.1Z"/></svg>
                        Login via Google
                    </a>
                @endif
            </div>
        </div>
    </aside>

    <main class="flex-1 flex flex-col relative overflow-hidden bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] bg-blend-overlay">
        
        <header class="h-20 border-b border-gray-800 glass-panel flex items-center justify-between px-10 shrink-0 z-10">
            <h2 class="text-xl font-bold">Workspace: <span class="text-indigo-400">All Folders</span></h2>
            <div class="flex items-center space-x-4">
                <button class="p-2 bg-gray-800 rounded-full hover:bg-gray-700 transition">
                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                </button>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto scrollbar-hide p-10 relative">
            
            @if(session('error'))
                <div class="mb-8 p-6 bg-red-900/20 border-l-4 border-red-500 rounded-xl glass-panel animate-pulse flex items-start">
                    <svg class="w-6 h-6 text-red-500 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <div>
                        <h3 class="text-red-400 font-bold uppercase text-xs">System Alert</h3>
                        <p class="text-red-200 text-sm mt-1">{{ session('error') }}</p>
                    </div>
                </div>
            @endif
            @if(session('success'))
                <div class="mb-8 p-4 bg-emerald-900/20 border border-emerald-500/30 text-emerald-400 rounded-xl text-sm font-bold flex items-center glass-panel">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    {{ session('success') }}
                </div>
            @endif

            @if(!session('google_token'))
                <div class="flex flex-col items-center justify-center h-full text-center">
                    <div class="w-24 h-24 bg-indigo-500/20 rounded-full flex items-center justify-center mb-6 border border-indigo-500/50">
                        <svg class="w-12 h-12 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </div>
                    <h2 class="text-4xl font-black mb-4">Akses Terkunci</h2>
                    <p class="text-gray-400 max-w-md mx-auto mb-8">Silakan login menggunakan akun Google Universitas Ma Chung Anda untuk mulai meramu mahakarya video.</p>
                </div>
            @else
                
                <div class="glass-panel p-8 rounded-3xl shadow-2xl mb-12 border-t border-gray-700 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl"></div>
                    
                    <form action="/search" method="GET" class="relative z-10 flex flex-col md:flex-row gap-4">
                        <div class="md:w-1/4">
                            <select name="folder_filter" class="w-full bg-gray-900 border border-gray-700 text-gray-300 rounded-2xl py-4 px-6 appearance-none focus:outline-none focus:border-indigo-500 transition cursor-pointer font-semibold">
                                <option value="all">🌐 Semua Workspace</option>
                                <option value="basket">📁 Ma Chung League</option>
                                <option value="debat">📁 Event SPORTA</option>
                            </select>
                        </div>
                        <div class="flex-1 relative">
                            <input type="text" name="q" value="{{ $query ?? '' }}" placeholder="Cari momen (cth: wajah tersenyum, shoot masuk)..." 
                                class="w-full bg-gray-900 border border-gray-700 text-white rounded-2xl py-4 px-6 pl-14 text-lg focus:outline-none focus:border-indigo-500 transition shadow-inner">
                            <svg class="w-6 h-6 text-gray-500 absolute left-5 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white rounded-2xl px-10 py-4 font-black shadow-[0_0_20px_rgba(79,70,229,0.3)] transition transform active:scale-95">
                            ANALISIS
                        </button>
                    </form>
                </div>

                @if(empty($query))
                    <div class="mb-10">
                        <h3 class="text-sm font-black text-gray-500 uppercase tracking-widest mb-6">Tambahkan Footage Baru</h3>
                        <div class="glass-panel p-6 rounded-3xl border border-gray-700/50 hover:border-indigo-500/30 transition group">
                            <form action="/google-drive/index" method="POST" class="flex flex-col md:flex-row gap-4 items-center">
                                @csrf
                                <input type="text" name="folder_id" placeholder="Google Drive Folder ID" required class="flex-1 bg-gray-900 border border-gray-700 text-white rounded-xl py-3 px-5 focus:border-indigo-500 outline-none text-sm">
                                <input type="text" name="prompt" placeholder="Instruksi AI Indexing..." required class="flex-1 bg-gray-900 border border-gray-700 text-white rounded-xl py-3 px-5 focus:border-indigo-500 outline-none text-sm">
                                <button type="submit" class="bg-gray-800 hover:bg-indigo-600 text-white border border-gray-700 font-bold py-3 px-8 rounded-xl transition w-full md:w-auto flex items-center justify-center group-hover:shadow-[0_0_15px_rgba(79,70,229,0.4)]">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                    Mulai Indexing
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

                @if(isset($query) && $query != '')
                    <div class="flex items-center justify-between mb-6 border-b border-gray-800 pb-4">
                        <h2 class="text-xl font-bold text-gray-200">Hasil untuk: <span class="text-indigo-400">"{{ $query }}"</span></h2>
                        <span class="bg-gray-800 text-gray-300 text-xs font-bold px-3 py-1 rounded-full border border-gray-700">{{ count($results) }} Klip Ditemukan</span>
                    </div>

                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 pb-20">
                        @foreach($results as $res)
                            <div class="glass-panel rounded-3xl overflow-hidden border border-gray-700 hover:border-indigo-500/50 transition duration-300 flex flex-col">
                                <div class="relative w-full bg-black aspect-video group">
                                    @if(!empty($res['file_id']))
                                        <video id="vid-{{ $loop->index }}" controls preload="metadata" class="w-full h-full object-cover">
                                            <source src="{{ route('video.stream', ['fileId' => $res['file_id']]) }}#t={{ $res['timestamp_seconds'] ?? 0 }}" type="video/mp4">
                                        </video>
                                    @else
                                        <div class="w-full h-full flex flex-col items-center justify-center text-gray-600">
                                            <svg class="w-12 h-12 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                            <span class="text-xs font-bold uppercase tracking-widest">Metadata Only</span>
                                        </div>
                                    @endif
                                    
                                    <div class="absolute top-4 right-4 bg-black/60 backdrop-blur text-white text-[10px] font-black px-3 py-1.5 rounded-full border border-white/10 flex items-center shadow-lg">
                                        🔥 Vibe Score: <span class="text-pink-400 ml-1">98%</span>
                                    </div>
                                </div>

                                <div class="p-6 flex-1 flex flex-col justify-between">
                                    <div>
                                        <div class="flex justify-between items-start mb-3">
                                            <span class="text-xs font-black text-indigo-400 bg-indigo-400/10 px-2 py-1 rounded border border-indigo-400/20">{{ $res['video'] }}</span>
                                            <span class="text-sm font-mono font-bold text-gray-400 bg-gray-800 px-2 py-1 rounded">⏱️ {{ $res['timestamp'] }}</span>
                                        </div>
                                        <p class="text-gray-300 text-sm leading-relaxed mb-6">
                                            {!! preg_replace('/('.preg_quote($query, '/').')/i', '<span class="bg-indigo-500/30 text-indigo-200 border-b-2 border-indigo-500 font-bold">$1</span>', $res['description']) !!}
                                        </p>
                                    </div>
                                    
                                    <div class="flex gap-2 mt-auto">
                                        <button onclick="exportEDL('{{ $res['video'] }}', '{{ $res['timestamp'] }}')" class="flex-1 bg-gray-800 hover:bg-indigo-600 text-white border border-gray-700 hover:border-indigo-500 text-[10px] font-black py-3 rounded-xl transition flex justify-center items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                            EDL (PREMIERE)
                                        </button>
                                        <button class="flex-1 bg-gray-800 hover:bg-pink-600 text-white border border-gray-700 hover:border-pink-500 text-[10px] font-black py-3 rounded-xl transition flex justify-center items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                            CUT (CAPCUT)
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
    </main>

    <script>
        function exportEDL(filename, timestamp) {
            const timeParts = timestamp.split(':');
            const seconds = parseInt(timeParts[0]) * 60 + parseInt(timeParts[1]);
            const startTime = new Date(seconds * 1000).toISOString().substr(11, 8) + ":00";
            const endTime = new Date((seconds + 5) * 1000).toISOString().substr(11, 8) + ":00";
            const edlContent = `TITLE: CINEMATCH AI\nFCM: NON-DROP FRAME\n\n001 AX V C ${startTime} ${endTime} ${startTime} ${endTime}\n* FROM CLIP NAME: ${filename}`;
            downloadFile(edlContent, filename.replace('.mp4','') + '.edl');
        }
        function downloadFile(content, fileName) {
            const blob = new Blob([content], { type: 'text/plain' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob); a.download = fileName; a.click();
        }
    </script>
</body>
</html>