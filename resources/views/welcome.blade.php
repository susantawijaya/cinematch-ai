<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>CineMatch AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white flex items-center justify-center min-h-screen">
    <div class="bg-gray-800 p-8 rounded-xl shadow-2xl w-full max-w-lg border border-gray-700">
        <h1 class="text-3xl font-bold text-blue-400 mb-6 text-center">CineMatch AI</h1>
        <form action="{{ route('analyze') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="file" name="frames[]" multiple accept="image/*" required class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:bg-blue-500 file:text-white cursor-pointer">
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg">Analisis Frame</button>
        </form>
    </div>
</body>
</html>
