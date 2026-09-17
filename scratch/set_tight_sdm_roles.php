<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== MENGATUR HAK AKSES SDM RAMPING (5 NAMA INTI) ===\n\n";

// 5 Nama Inti SDM yang berhak memiliki akses Admin SDM di web:
$allowedSdmNiks = [
    '1711254', // Heddy Kelana, S.H. (Manajer Bidang Sumber Daya Manusia)
    '1711157', // Cahrudin, S.E. (Asisten Manajer Pembinaan SDM & K3)
    '1711444', // Suwanto, A.Md. (Staf Administrasi SDM / Operator Penggajian)
    '1711590', // Riko Prahtama, S.Kom. (Staf Pembinaan SDM & K3 / Operator)
    '1711567', // Asep Kurnadi (Staf Administrasi SDM / Operator)
];

// 1. Pastikan 5 nama inti memiliki role 'sdm'
foreach ($allowedSdmNiks as $nik) {
    $p = DB::table('pegawai')->where('nik', $nik)->first();
    if ($p) {
        DB::table('pegawai')->where('nik', $nik)->update(['role' => 'sdm']);
        echo "[SDM AKTIF] NIK {$nik} | {$p->name} ({$p->jabatan}) => role 'sdm'\n";
    }
}

echo "\n=== MENGEMBALIKAN STAF SDM LAINNYA KE ROLE 'pegawai' ===\n";
// 2. Kembalikan staf SDM lainnya ke 'pegawai'
$revertSdmNiks = [
    '1711252', // Intan Wulansari
    '1711309', // Riyanto, S.H.
    '1711392', // Maya Sari Dewi
    '1711561', // Dita Ambarini
    '1711619', // Ega Hadiyanto
    '1711676', // Alfiah Khairunnisa, S.Pd.
    'TEMP0050', // Zahroh, S.M.
    '1711357', // Teddy Ramadhani
    '1711420', // Lola Juliyanti
    '1711453', // Arif Gunawan
];

foreach ($revertSdmNiks as $nik) {
    $p = DB::table('pegawai')->where('nik', $nik)->first();
    if ($p) {
        DB::table('pegawai')->where('nik', $nik)->update(['role' => 'pegawai']);
        echo "[KEMBALI KE PEGAWAI] NIK {$nik} | {$p->name} ({$p->jabatan}) => role 'pegawai'\n";
    }
}

echo "\n=== DAFTAR SEMUA AKUN DENGAN ROLE SDM SEKARANG ===\n";
$currentSdm = DB::table('pegawai')->where('role', 'sdm')->select('nik', 'name', 'jabatan', 'role')->get();
foreach ($currentSdm as $cs) {
    echo "NIK: {$cs->nik} | Nama: {$cs->name} | Jabatan: {$cs->jabatan} | Role: {$cs->role}\n";
}

echo "\n=== SELESAI ===\n";
