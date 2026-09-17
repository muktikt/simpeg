<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== MEMPERBARUI HAK AKSES ROLE DI DATABASE PEGAWAI ===\n\n";

// 1. Update Manajer & Tim Administrasi SDM ke role 'sdm'
$sdmNiks = [
    '1711254', // Heddy Kelana, S.H. (Manajer Bidang Sumber Daya Manusia)
    '1711252', // Intan Wulansari (Asisten Manajer Administrasi SDM)
    '1711444', // Suwanto, A.Md. (Staf Administrasi SDM)
    '1711561', // Dita Ambarini (Staf Administrasi SDM)
    '1711567', // Asep Kurnadi (Staf Administrasi SDM)
    '1711619', // Ega Hadiyanto (Staf Administrasi SDM)
];

foreach ($sdmNiks as $nik) {
    $pegawai = DB::table('pegawai')->where('nik', $nik)->first();
    if ($pegawai) {
        DB::table('pegawai')->where('nik', $nik)->update(['role' => 'sdm']);
        echo "[UPDATE SDM] NIK {$nik} | {$pegawai->name} ({$pegawai->jabatan}) => role diubah menjadi 'sdm'\n";
    }
}

echo "\n";

// 2. Update Asisten Manajer Bagian Keuangan Kantor Pusat ke role 'keuangan'
$keuNiks = [
    '1711357', // Teddy Ramadhani N., S.E. (Asisten Manajer Kas)
    '1711420', // Lola Juliyanti, S.E. (Asisten Manajer Akuntansi)
    '1711453', // Arif Gunawan, S.E (Plt. Asisten Manajer Verifikasi Anggaran)
];

foreach ($keuNiks as $nik) {
    $pegawai = DB::table('pegawai')->where('nik', $nik)->first();
    if ($pegawai) {
        DB::table('pegawai')->where('nik', $nik)->update(['role' => 'keuangan']);
        echo "[UPDATE KEUANGAN] NIK {$nik} | {$pegawai->name} ({$pegawai->jabatan}) => role diubah menjadi 'keuangan'\n";
    }
}

echo "\n=== VERIFIKASI AKUN SETELAH UPDATE ===\n";
$allNiks = array_merge($sdmNiks, $keuNiks);
$verified = DB::table('pegawai')->whereIn('nik', $allNiks)->select('nik', 'name', 'jabatan', 'role')->get();
foreach ($verified as $v) {
    echo "NIK: {$v->nik} | Nama: {$v->name} | Jabatan: {$v->jabatan} | Role: {$v->role}\n";
}

echo "\n=== SELESAI ===\n";
