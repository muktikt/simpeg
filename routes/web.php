<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PlaceholderController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\PegawaiDetailController;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\GajiPokokController;
use App\Http\Controllers\DrdTukinController;
use App\Http\Controllers\SanksiController;
use App\Http\Controllers\PrestasiController;
use App\Http\Controllers\GajiProsesController;
use App\Http\Controllers\ThrController;
use App\Http\Controllers\GajiTigabelasController;

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

            // Angkat Calon Pegawai (CP) jadi Pegawai Tetap (PT) + ganti NIK.
            Route::post('/{id}/angkat-tetap', [PegawaiController::class, 'promoteToTetap'])->whereNumber('id')->name('promote-to-tetap');
        });
    });

    // Absensi - Read boleh semua role yang login, Create/Update/Delete cuma Admin.
    Route::prefix('absensi')->name('absensi.')->group(function () {
        Route::get('/', [AbsensiController::class, 'index'])->name('index');

        Route::middleware(['simpeg.auth:1'])->group(function () {
            Route::get('/create', [AbsensiController::class, 'create'])->name('create');
            Route::post('/', [AbsensiController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [AbsensiController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::put('/{id}', [AbsensiController::class, 'update'])->whereNumber('id')->name('update');
            Route::delete('/{id}', [AbsensiController::class, 'destroy'])->whereNumber('id')->name('destroy');
        });
    });

    // Absensi - Read boleh Admin/Keuangan/Direksi, tambah/edit/hapus cuma Admin.
    Route::prefix('absensi')->name('absensi.')->group(function () {
        Route::get('/', [AbsensiController::class, 'index'])->name('index');
        Route::get('/laporan', [AbsensiController::class, 'laporan'])->name('laporan');

        Route::middleware(['simpeg.auth:1'])->group(function () {
            Route::get('/create', [AbsensiController::class, 'create'])->name('create');
            Route::post('/', [AbsensiController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [AbsensiController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::put('/{id}', [AbsensiController::class, 'update'])->whereNumber('id')->name('update');
            Route::delete('/{id}', [AbsensiController::class, 'destroy'])->whereNumber('id')->name('destroy');
        });
    });

    // Gaji Pokok - Read boleh Admin & Keuangan. Tambah/Edit cuma Admin.
    // Catatan: sistem lama TIDAK punya fitur hapus untuk gaji pokok, jadi di sini juga tidak ada.
    Route::prefix('gaji-pokok')->name('gaji-pokok.')->group(function () {
        Route::middleware(['simpeg.auth:1,2,7'])->group(function () {
            Route::get('/', [GajiPokokController::class, 'index'])->name('index');
        });

        Route::middleware(['simpeg.auth:1'])->group(function () {
            Route::get('/create', [GajiPokokController::class, 'create'])->name('create');
            Route::post('/', [GajiPokokController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [GajiPokokController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::put('/{id}', [GajiPokokController::class, 'update'])->whereNumber('id')->name('update');
        });
    });

    // DRD Tukin - full CRUD, Admin & Keuangan (sesuai grup menu Pengaturan Proses Gaji).
    Route::prefix('drd-tukin')->name('drd-tukin.')->middleware(['simpeg.auth:1,2'])->group(function () {
        Route::get('/', [DrdTukinController::class, 'index'])->name('index');
        Route::get('/create', [DrdTukinController::class, 'create'])->name('create');
        Route::post('/', [DrdTukinController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [DrdTukinController::class, 'edit'])->whereNumber('id')->name('edit');
        Route::put('/{id}', [DrdTukinController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}', [DrdTukinController::class, 'destroy'])->whereNumber('id')->name('destroy');
    });

    // Sanksi Pegawai - index bisa dilihat semua role yang login (dipakai juga
    // buat menu "Lap. Sanksi Pegawai"), tambah/edit/hapus cuma Admin & Keuangan.
    Route::prefix('sanksi')->name('sanksi.')->group(function () {
        Route::get('/', [SanksiController::class, 'index'])->name('index');

        Route::middleware(['simpeg.auth:1,2'])->group(function () {
            Route::get('/create', [SanksiController::class, 'create'])->name('create');
            Route::post('/', [SanksiController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [SanksiController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::put('/{id}', [SanksiController::class, 'update'])->whereNumber('id')->name('update');
            Route::delete('/{id}', [SanksiController::class, 'destroy'])->whereNumber('id')->name('destroy');
        });
    });

    // Prestasi (rekap kerja bulanan untuk gaji) - index bisa dilihat semua role
    // login, tambah/edit/hapus cuma Admin & Keuangan.
    Route::prefix('prestasi')->name('prestasi.')->group(function () {
        Route::get('/', [PrestasiController::class, 'index'])->name('index');

        Route::middleware(['simpeg.auth:1,2'])->group(function () {
            Route::get('/create', [PrestasiController::class, 'create'])->name('create');
            Route::post('/', [PrestasiController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [PrestasiController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::put('/{id}', [PrestasiController::class, 'update'])->whereNumber('id')->name('update');
            Route::delete('/{id}', [PrestasiController::class, 'destroy'])->whereNumber('id')->name('destroy');
        });
    });

    // Proses Gaji Bulanan - Admin & Keuangan.
    Route::prefix('gaji-proses')->name('gaji-proses.')->middleware(['simpeg.auth:1,2'])->group(function () {
        Route::get('/', [GajiProsesController::class, 'index'])->name('index');
        Route::get('/create', [GajiProsesController::class, 'create'])->name('create');
        Route::post('/', [GajiProsesController::class, 'store'])->name('store');
        Route::get('/{id}', [GajiProsesController::class, 'show'])->whereNumber('id')->name('show');
        Route::post('/{id}/terbitkan', [GajiProsesController::class, 'terbitkan'])->whereNumber('id')->name('terbitkan');
        Route::delete('/{id}', [GajiProsesController::class, 'destroy'])->whereNumber('id')->name('destroy');
        Route::get('/ajax/hitung-keluarga/{pegawaiId}', [GajiProsesController::class, 'hitungKeluargaJson'])->whereNumber('pegawaiId')->name('hitung-keluarga');
    });

    // THR - index/show bisa dilihat Admin, Keuangan, dan Direksi.
    // Tambah/Terbitkan/Hapus cuma Admin & Keuangan.
    Route::prefix('thr')->name('thr.')->group(function () {
        Route::middleware(['simpeg.auth:1,2,7'])->group(function () {
            Route::get('/', [ThrController::class, 'index'])->name('index');
            Route::get('/{id}', [ThrController::class, 'show'])->whereNumber('id')->name('show');
        });

        Route::middleware(['simpeg.auth:1,2'])->group(function () {
            Route::get('/create', [ThrController::class, 'create'])->name('create');
            Route::post('/', [ThrController::class, 'store'])->name('store');
            Route::post('/{id}/terbitkan', [ThrController::class, 'terbitkan'])->whereNumber('id')->name('terbitkan');
            Route::delete('/{id}', [ThrController::class, 'destroy'])->whereNumber('id')->name('destroy');
            Route::get('/ajax/hitung-keluarga/{pegawaiId}', [ThrController::class, 'hitungKeluargaJson'])->whereNumber('pegawaiId')->name('hitung-keluarga');
        });
    });

    // Gaji 13 / Tunjangan Pendidikan - satu modul yang sama (dicek dari
    // menu_incl.php asli, "Laporan Tunj. Pendidikan" mengarah ke file yang
    // sama dengan Gaji 13). Index/show bisa dilihat Admin, Keuangan, Direksi.
    Route::prefix('gaji-tigabelas')->name('gaji-tigabelas.')->group(function () {
        Route::middleware(['simpeg.auth:1,2,7'])->group(function () {
            Route::get('/', [GajiTigabelasController::class, 'index'])->name('index');
            Route::get('/{id}', [GajiTigabelasController::class, 'show'])->whereNumber('id')->name('show');
        });

        Route::middleware(['simpeg.auth:1,2'])->group(function () {
            Route::get('/create', [GajiTigabelasController::class, 'create'])->name('create');
            Route::post('/', [GajiTigabelasController::class, 'store'])->name('store');
            Route::post('/{id}/terbitkan', [GajiTigabelasController::class, 'terbitkan'])->whereNumber('id')->name('terbitkan');
            Route::delete('/{id}', [GajiTigabelasController::class, 'destroy'])->whereNumber('id')->name('destroy');
            Route::get('/ajax/hitung-keluarga/{pegawaiId}', [GajiTigabelasController::class, 'hitungKeluargaJson'])->whereNumber('pegawaiId')->name('hitung-keluarga');
        });
    });

    // Semua modul lama yang belum dimigrasikan -> halaman placeholder.
    Route::get('/modul/{slug}', [PlaceholderController::class, 'show'])->name('placeholder');
});
