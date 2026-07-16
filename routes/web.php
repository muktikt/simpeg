<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PlaceholderController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\PegawaiDetailController;

Route::get('/', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['simpeg.auth'])->group(function () {
    Route::get('/beranda', [DashboardController::class, 'index'])->name('dashboard');

    // Data Pegawai - Read boleh semua role yang login, Create/Update/Delete cuma Admin.
    Route::prefix('pegawai')->name('pegawai.')->group(function () {
        Route::get('/', [PegawaiController::class, 'index'])->name('index');
        Route::get('/{id}', [PegawaiController::class, 'show'])->whereNumber('id')->name('show');

        Route::middleware(['simpeg.auth:1'])->group(function () {
            Route::get('/create', [PegawaiController::class, 'create'])->name('create');
            Route::post('/', [PegawaiController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [PegawaiController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::put('/{id}', [PegawaiController::class, 'update'])->whereNumber('id')->name('update');
            Route::delete('/{id}', [PegawaiController::class, 'destroy'])->whereNumber('id')->name('destroy');

            Route::post('/{id}/detail/{type}', [PegawaiDetailController::class, 'store'])->whereNumber('id')->name('detail.store');
            Route::put('/{id}/detail/{type}/{itemId}', [PegawaiDetailController::class, 'update'])->whereNumber('id')->whereNumber('itemId')->name('detail.update');
            Route::delete('/{id}/detail/{type}/{itemId}', [PegawaiDetailController::class, 'destroy'])->whereNumber('id')->whereNumber('itemId')->name('detail.destroy');
        });
    });

    // Semua modul lama yang belum dimigrasikan -> halaman placeholder.
    Route::get('/modul/{slug}', [PlaceholderController::class, 'show'])->name('placeholder');
});
