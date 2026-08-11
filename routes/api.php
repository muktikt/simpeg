<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiPegawaiController;

/*
|--------------------------------------------------------------------------
| API Routes untuk SIMPEG Mobile (Flutter Integration)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Auth API
    Route::post('/login', [ApiPegawaiController::class, 'login']);
    Route::post('/ganti-password', [ApiPegawaiController::class, 'gantiPassword']);

    // Data Pegawai & Profil
    Route::get('/profile', [ApiPegawaiController::class, 'profile']);

    // Keuangan & Payroll
    Route::get('/payroll/slip-gaji', [ApiPegawaiController::class, 'slipGaji']);
    Route::get('/payroll/thr', [ApiPegawaiController::class, 'slipThr']);
    Route::get('/payroll/gaji-13', [ApiPegawaiController::class, 'slipGaji13']);
    Route::get('/payroll/insentif', [ApiPegawaiController::class, 'insentif']);

    // Absensi Kehadiran
    Route::get('/absensi', [ApiPegawaiController::class, 'absensi']);
    Route::post('/absensi/checkin', [ApiPegawaiController::class, 'checkinAbsensi']);

    // Sanksi & Prestasi
    Route::get('/sanksi', [ApiPegawaiController::class, 'sanksi']);
    Route::get('/prestasi', [ApiPegawaiController::class, 'prestasi']);

    // Pengajuan Cuti & Lembur
    Route::get('/cuti', [ApiPegawaiController::class, 'getCuti']);
    Route::post('/cuti', [ApiPegawaiController::class, 'storeCuti']);
    Route::post('/lembur', [ApiPegawaiController::class, 'storeLembur']);

    // Pengaduan Pegawai
    Route::get('/pengaduan', [ApiPegawaiController::class, 'pengaduan']);
    Route::post('/pengaduan', [ApiPegawaiController::class, 'storePengaduan']);
});
