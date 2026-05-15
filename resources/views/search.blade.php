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

        @if(isset($query))
            <h2 class="text-xl text-gray-300 mb-6">
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