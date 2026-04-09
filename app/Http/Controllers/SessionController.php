<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function index(Request $request): View
    {
        $sessionData = $request->session()->all();

        return view('dashboard', [
            'sessionData' => $sessionData,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:255'],
            'value' => ['required', 'string', 'max:1000'],
        ]);

        $request->session()->put('custom.'.$validated['key'], $validated['value']);

        return redirect()->route('dashboard')->with('status', "Added '{$validated['key']}' to session.");
    }

    public function clear(Request $request): RedirectResponse
    {
        $request->session()->forget('custom');

        return redirect()->route('dashboard')->with('status', 'Custom session data cleared.');
    }

    public function raw(Request $request): View
    {
        $sessionId = $request->session()->getId();
        $decryptedData = $request->session()->all();

        // phpredis auto-prepends the prefix from config/database.php redis.options.prefix
        // so we just need the session ID as the key
        $rawData = Redis::get($sessionId);

        if ($rawData === null) {
            // Fallback: search for the key in case the prefix differs
            $allKeys = Redis::keys('*'.$sessionId.'*');
            $rawData = "Key not found for session ID: {$sessionId}";

            if (! empty($allKeys)) {
                $rawData .= "\n\nFound matching keys:\n";
                foreach ($allKeys as $key) {
                    $rawData .= "  - {$key}\n";
                    $rawData .= '    Value: '.Redis::get($key)."\n";
                }
            }
        }

        return view('session-raw', [
            'sessionId' => $sessionId,
            'rawData' => $rawData,
            'decryptedData' => $decryptedData,
            'redisPrefix' => config('database.redis.options.prefix'),
        ]);
    }
}
