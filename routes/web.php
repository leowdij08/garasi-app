<?php

use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware('auth')->group(function () {
    // Mengarahkan pengguna ke dashboard sesuai role-nya.
    Route::get('/dashboard', function () {
        return auth()->user()->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('penyewa.dashboard');
    })->name('dashboard');

    Route::middleware(EnsureRole::class.':ADMIN')
        ->prefix('admin')->name('admin.')
        ->group(function () {
            Route::view('/', 'admin.dashboard')->name('dashboard');
        });

    Route::middleware(EnsureRole::class.':PENYEWA')
        ->prefix('penyewa')->name('penyewa.')
        ->group(function () {
            Route::view('/', 'penyewa.dashboard')->name('dashboard');
        });
});

require __DIR__.'/auth.php';