<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PlaceholderController;

Route::get('/', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['simpeg.auth'])->group(function () {
    Route::get('/beranda', [DashboardController::class, 'index'])->name('dashboard');

    // Semua modul lama yang belum dimigrasikan -> halaman placeholder.
    Route::get('/modul/{slug}', [PlaceholderController::class, 'show'])->name('placeholder');
});
