<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== USER DENGAN ROLE SDM / ADMIN (USERLEVEL 1) ===\n";
$sdmUsers = DB::table('pegawai')
    ->whereIn(DB::raw('LOWER(role)'), ['sdm', 'admin'])
    ->select('nik', 'name', 'jabatan', 'role')
    ->orderBy('nik')
    ->get();
foreach ($sdmUsers as $u) {
    echo "NIK: {$u->nik} | Nama: {$u->name} | Jabatan: {$u->jabatan} | Role: {$u->role}\n";
}

echo "\n=== USER DENGAN ROLE KEUANGAN (USERLEVEL 2) ===\n";
$keuUsers = DB::table('pegawai')
    ->whereIn(DB::raw('LOWER(role)'), ['keuangan', 'keu'])
    ->select('nik', 'name', 'jabatan', 'role')
    ->orderBy('nik')
    ->get();
foreach ($keuUsers as $u) {
    echo "NIK: {$u->nik} | Nama: {$u->name} | Jabatan: {$u->jabatan} | Role: {$u->role}\n";
}

echo "\n=== USER DENGAN ROLE DIREKTUR (USERLEVEL 7) ===\n";
$dirUsers = DB::table('pegawai')
    ->whereIn(DB::raw('LOWER(role)'), ['direktur'])
    ->select('nik', 'name', 'jabatan', 'role')
    ->orderBy('nik')
    ->get();
foreach ($dirUsers as $u) {
    echo "NIK: {$u->nik} | Nama: {$u->name} | Jabatan: {$u->jabatan} | Role: {$u->role}\n";
}

echo "\n=== USER DENGAN ROLE KHUSUS LAINNYA (KSPI, KADIV, KASIR) ===\n";
$otherUsers = DB::table('pegawai')
    ->whereIn(DB::raw('LOWER(role)'), ['kspi', 'kadiv', 'kadivkategori', 'tpdpk'])
    ->select('nik', 'name', 'jabatan', 'role', 'divisi_kadiv')
    ->orderBy('role')
    ->orderBy('nik')
    ->get();
foreach ($otherUsers as $u) {
    echo "NIK: {$u->nik} | Nama: {$u->name} | Jabatan: {$u->jabatan} | Role: {$u->role} | Divisi: {$u->divisi_kadiv}\n";
}

echo "\n=== TOTAL PEGAWAI DI DATABASE ===\n";
echo "Total Pegawai: " . DB::table('pegawai')->count() . "\n";
