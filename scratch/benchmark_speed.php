<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function benchmark($title, $callback) {
    $start = microtime(true);
    $res = $callback();
    $dur = (microtime(true) - $start) * 1000;
    echo sprintf("[BENCHMARK] %-40s => %7.2f ms\n", $title, $dur);
    return $res;
}

echo "=== MEMULAI TEST KECEPATAN SETIAP FITUR WEB SIMPEG ===\n\n";

// 1. Dashboard
benchmark("1. Load Dashboard (Cold/Cached)", function() {
    $ctrl = app(\App\Http\Controllers\DashboardController::class);
    return $ctrl->index();
});

benchmark("2. Load Dashboard (Warm Cache)", function() {
    $ctrl = app(\App\Http\Controllers\DashboardController::class);
    return $ctrl->index();
});

// 2. Pegawai List & Search
benchmark("3. Buka Daftar Pegawai (all)", function() {
    $ctrl = app(\App\Http\Controllers\PegawaiController::class);
    return $ctrl->index(new \Illuminate\Http\Request());
});

benchmark("4. Search Pegawai ('budi')", function() {
    $ctrl = app(\App\Http\Controllers\PegawaiController::class);
    $req = new \Illuminate\Http\Request(['q' => 'budi']);
    return $ctrl->index($req);
});

// 3. Dokumen Surat
benchmark("5. Buka Menu Dokumen Surat", function() {
    $ctrl = app(\App\Http\Controllers\DokumenSuratController::class);
    return $ctrl->index(new \Illuminate\Http\Request());
});

benchmark("6. Search Dokumen Surat ('3000000003')", function() {
    $ctrl = app(\App\Http\Controllers\DokumenSuratController::class);
    $req = new \Illuminate\Http\Request(['q' => '3000000003']);
    return $ctrl->index($req);
});

// 4. Pengumuman
benchmark("7. Buka Pengumuman List", function() {
    $ctrl = app(\App\Http\Controllers\PengumumanController::class);
    return $ctrl->index(new \Illuminate\Http\Request());
});

// 5. Potongan Keuangan
benchmark("8. Buka Potongan Keuangan (Set Potongan)", function() {
    $ctrl = app(\App\Http\Controllers\PotonganKeuController::class);
    return $ctrl->index(new \Illuminate\Http\Request());
});

echo "\n=== BENCHMARK SELESAI ===\n";
