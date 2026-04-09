<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Session Migration Demo</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gray-100">
    <nav class="bg-white shadow-sm">
        <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
            <h1 class="text-lg font-semibold">Session Migration Demo</h1>
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-600">{{ auth()->user()->name }} ({{ auth()->user()->email }})</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-red-600 hover:underline">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-8">
        @if (session('status'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded text-green-700 text-sm">
                {{ session('status') }}
            </div>
        @endif

        {{-- Add Session Data --}}
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Add Session Data</h2>
            <form method="POST" action="{{ route('session.store') }}" class="flex gap-3">
                @csrf
                <input type="text" name="key" placeholder="Key" required
                    class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <input type="text" name="value" placeholder="Value" required
                    class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                    Add
                </button>
            </form>
            @if ($errors->any())
                <div class="mt-2 text-red-600 text-sm">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Session Data Display --}}
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold">Session Data (Decrypted from Redis)</h2>
                <div class="flex gap-2">
                    <a href="{{ route('session.raw') }}" class="text-sm bg-gray-600 text-white px-3 py-1 rounded hover:bg-gray-700 transition">
                        View Raw Redis Data
                    </a>
                    <form method="POST" action="{{ route('session.clear') }}">
                        @csrf
                        <button type="submit" class="text-sm bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 transition">
                            Clear Custom Data
                        </button>
                    </form>
                </div>
            </div>

            <pre class="bg-gray-900 text-green-400 p-4 rounded-md overflow-x-auto text-sm font-mono whitespace-pre-wrap">{{ json_encode($sessionData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>

        {{-- Info --}}
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
            <p class="font-semibold mb-1">How this works:</p>
            <ul class="list-disc list-inside space-y-1">
                <li>Session driver: <code class="bg-blue-100 px-1 rounded">redis</code></li>
                <li>Session encryption: <code class="bg-blue-100 px-1 rounded">{{ config('session.encrypt') ? 'enabled' : 'disabled' }}</code></li>
                <li>Session serialization: <code class="bg-blue-100 px-1 rounded">{{ config('session.serialization') }}</code></li>
                <li>Session ID: <code class="bg-blue-100 px-1 rounded">{{ session()->getId() }}</code></li>
                <li>The data above is stored encrypted in Redis and decrypted by Laravel on each request.</li>
                <li>Migrating the Redis data to another server with the same APP_KEY preserves all sessions.</li>
            </ul>
        </div>
    </div>
</body>
</html>
