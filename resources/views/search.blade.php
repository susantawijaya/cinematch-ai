<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Synkora Studio - Workspace</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script type="text/javascript" src="https://apis.google.com/js/api.js"></script>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="antialiased">
    <div id="react-app" 
         data-csrf-token="{{ csrf_token() }}"
         data-query="{{ $query ?? '' }}"
         data-results="{{ json_encode($results ?? []) }}"
         data-all-assets="{{ json_encode($all_assets ?? []) }}"
         data-chat-messages="{{ json_encode($chat_messages ?? []) }}"
         data-google-token="{{ session('google_token') ?? '' }}"
         data-api-key="{{ env('GOOGLE_API_KEY') ?? '' }}"
         data-client-id="{{ env('GOOGLE_CLIENT_ID') ?? '' }}">
    </div>
</body>
</html>