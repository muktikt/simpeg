<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiPegawaiController;

/*
|--------------------------------------------------------------------------
| API Routes untuk SIMPEG Mobile (Flutter Integration)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // 1. Auth API Publik (dilindungi Rate Limiter / Throttling)
    Route::post('/login', [ApiPegawaiController::class, 'login'])->middleware('throttle:5,1');

    // 2. Seluruh Endpoint Operasional Wajib Terautentikasi Token Pegawai
    Route::middleware('api.key')->group(function () {
        // Ganti Password (Wajib Terautentikasi)
        Route::post('/ganti-password', [ApiPegawaiController::class, 'gantiPassword']);

        // Data Pegawai & Profil
        Route::get('/profile', [ApiPegawaiController::class, 'profile']);
        Route::get('/pegawai', [ApiPegawaiController::class, 'listPegawai']);

        // Keuangan & Payroll
        Route::get('/payroll/slip-gaji', [ApiPegawaiController::class, 'slipGaji']);
        Route::get('/payroll/thr', [ApiPegawaiController::class, 'slipThr']);
        Route::get('/payroll/gaji-13', [ApiPegawaiController::class, 'slipGaji13']);
        Route::get('/payroll/tunjangan-pendidikan', [ApiPegawaiController::class, 'tunjanganPendidikan']);
        Route::get('/payroll/insentif', [ApiPegawaiController::class, 'insentif']);

        // Absensi Kehadiran
        Route::get('/absensi', [ApiPegawaiController::class, 'absensi']);
        Route::post('/absensi/checkin', [ApiPegawaiController::class, 'checkinAbsensi']);
        Route::post('/absensi/checkout', [ApiPegawaiController::class, 'checkoutAbsensi']);

        // Sanksi & Prestasi
        Route::get('/sanksi', [ApiPegawaiController::class, 'sanksi']);
        Route::get('/prestasi', [ApiPegawaiController::class, 'prestasi']);

        // Pengajuan Cuti & Lembur
        Route::get('/cuti', [ApiPegawaiController::class, 'getCuti']);
        Route::post('/cuti', [ApiPegawaiController::class, 'storeCuti']);
        Route::put('/cuti/{id}/status', [ApiPegawaiController::class, 'updateCutiStatus']);
        Route::get('/lembur', [ApiPegawaiController::class, 'getLembur']);
        Route::get('/payroll/lembur', [ApiPegawaiController::class, 'getLembur']);
        Route::post('/lembur', [ApiPegawaiController::class, 'storeLembur']);

        // Pengaduan Pegawai
        Route::get('/pengaduan', [ApiPegawaiController::class, 'pengaduan']);
        Route::post('/pengaduan', [ApiPegawaiController::class, 'storePengaduan']);

        // Dokumen Resmi Kepegawaian (SK & Diklat dari SDM)
        Route::get('/dokumen', [ApiPegawaiController::class, 'dokumenResmi']);
        Route::get('/dokumen/{id}/download', [ApiPegawaiController::class, 'downloadDokumen']);

        // Pengumuman Perusahaan
        Route::get('/pengumuman', [ApiPegawaiController::class, 'pengumuman']);
        Route::post('/pengumuman/{id}/baca', [ApiPegawaiController::class, 'markPengumumanRead']);
    });
});
