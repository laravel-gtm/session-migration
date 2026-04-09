<?php

use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [SessionController::class, 'index'])->name('dashboard');
    Route::post('/session', [SessionController::class, 'store'])->name('session.store');
    Route::post('/session/clear', [SessionController::class, 'clear'])->name('session.clear');
    Route::get('/session/raw', [SessionController::class, 'raw'])->name('session.raw');
});
