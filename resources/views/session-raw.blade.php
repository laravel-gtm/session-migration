<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raw Session Data - Session Migration Demo</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gray-100">
    <nav class="bg-white shadow-sm">
        <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
            <h1 class="text-lg font-semibold">Session Migration Demo</h1>
            <a href="{{ route('dashboard') }}" class="text-sm text-blue-600 hover:underline">Back to Dashboard</a>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-lg font-semibold mb-2">Session ID</h2>
            <code class="bg-gray-100 px-2 py-1 rounded text-sm">{{ $sessionId }}</code>
            <p class="text-sm text-gray-500 mt-2">Redis key: <code class="bg-gray-100 px-1 rounded">{{ $redisPrefix }}{{ $sessionId }}</code></p>
        </div>

        {{-- Raw Encrypted Data --}}
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Raw Data from Redis (Encrypted)</h2>
            <p class="text-sm text-gray-500 mb-2">This is what's actually stored in Redis — encrypted and unreadable without the APP_KEY.</p>
            <pre class="bg-gray-900 text-red-400 p-4 rounded-md overflow-x-auto text-sm font-mono whitespace-pre-wrap break-all">{{ $rawData }}</pre>
        </div>

        {{-- Decrypted Data --}}
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Decrypted Session Data (via Laravel)</h2>
            <p class="text-sm text-gray-500 mb-2">Laravel automatically decrypts this on each request using your APP_KEY.</p>
            <pre class="bg-gray-900 text-green-400 p-4 rounded-md overflow-x-auto text-sm font-mono whitespace-pre-wrap">{{ json_encode($decryptedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>

        {{-- Migration Note --}}
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-800">
            <p class="font-semibold mb-1">Session Migration Note:</p>
            <p>To migrate sessions to a new server or Redis instance without logging users out:</p>
            <ol class="list-decimal list-inside mt-2 space-y-1">
                <li>Copy the Redis data (use <code class="bg-yellow-100 px-1 rounded">DUMP</code>/<code class="bg-yellow-100 px-1 rounded">RESTORE</code> or <code class="bg-yellow-100 px-1 rounded">redis-cli --rdb</code>)</li>
                <li>Ensure the new server has the same <code class="bg-yellow-100 px-1 rounded">APP_KEY</code> in <code class="bg-yellow-100 px-1 rounded">.env</code></li>
                <li>Point DNS or load balancer to the new server</li>
                <li>Users remain logged in because their session cookies still match valid Redis keys, and the APP_KEY can decrypt the data</li>
            </ol>
        </div>
    </div>
</body>
</html>
