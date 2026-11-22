<?php

use App\Http\Controllers\Admin\GarageController;
use App\Http\Controllers\Admin\TenantVerificationController;
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

            Route::get('/garasi', [GarageController::class, 'index'])->name('garages.index');
            Route::get('/garasi/buat', [GarageController::class, 'create'])->name('garages.create');
            Route::post('/garasi', [GarageController::class, 'store'])->name('garages.store');

            Route::get('/penyewa', [TenantVerificationController::class, 'index'])->name('tenants.index');
            Route::post('/penyewa/{tenant}/setujui', [TenantVerificationController::class, 'setujui'])->name('tenants.setujui');
            Route::post('/penyewa/{tenant}/tolak', [TenantVerificationController::class, 'tolak'])->name('tenants.tolak');
        });

    Route::middleware(EnsureRole::class.':PENYEWA')
        ->prefix('penyewa')->name('penyewa.')
        ->group(function () {
            Route::view('/', 'penyewa.dashboard')->name('dashboard');
        });
});

require __DIR__.'/auth.php';