<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\GajiProsesController;
use App\Http\Controllers\ApprovalController;
use Illuminate\Http\Request;

echo "=== TEST PERSISTENT GAJI PROSES & APPROVAL ===\n\n";

$gajiCtrl = app(GajiProsesController::class);

// 1. Ambil data pegawai contoh (misal Heddy Kelana)
$pegawaiList = app(\App\Http\Controllers\PegawaiController::class)->all();
$samplePegawai = $pegawaiList[0] ?? null;

echo "Sample Pegawai: {$samplePegawai['nama']} (NIK: {$samplePegawai['nik']}, ID: {$samplePegawai['id']})\n";

// 2. Simpan satu draft proses gaji
$testGaji = [
    [
        'id' => 1,
        'pegawai_id' => $samplePegawai['id'],
        'nik' => $samplePegawai['nik'],
        'nama' => $samplePegawai['nama'],
        'jabatan' => $samplePegawai['jabatan'],
        'unit_kerja' => $samplePegawai['unit_kerja'],
        'kategori' => 'satuan',
        'kode_ptkp' => 'K1',
        'bulan' => 9,
        'tahun' => 2026,
        'status' => 'draft',
        'gapok' => 4500000,
        'tunjangan_istri' => 450000,
        'tunjangan_anak' => 200000,
        'tunjangan_prestasi' => 0,
        'tunjangan_jabatan' => 1200000,
        'tunjangan_transport' => 300000,
        'tunjangan_pangan' => 250000,
        'tunjangan_bpjstk' => 120000,
        'tunjangan_perumahan' => 500000,
        'tunjangan_perusahaan' => 0,
        'tunjangan_airminum' => 0,
        'tunjangan_bpjskes' => 150000,
        'tunjangan_komunikasi' => 0,
        'tunjangan_pajak' => 0,
        'lembur' => 0,
        'potongan_sanksi' => 0,
        'potongan_dapenma' => 200000,
        'potongan_bpjstk' => 120000,
        'potongan_bpjskes' => 150000,
        'potongan_perumahan' => 0,
        'potongan_pajak' => 50000,
        'potongan_korpri' => 20000,
        'potongan_tperusahaan' => 0,
        'potongan_lain' => 0,
        'potongan_koperasi' => 100000,
        'potongan_darmawanita' => 0,
        'potongan_ledeng' => 0,
        'potongan_kas' => 0,
        'potongan_bjb' => 0,
        'potongan_bjbs' => 0,
        'potongan_asuransi' => 0,
        'potongan_btn' => 0,
        'potongan_bpr' => 0,
        'potongan_zakat' => 0,
        'total_pendapatan' => 7470000,
        'total_potongan' => 640000,
        'gaji_bersih' => 6830000,
    ]
];

$gajiCtrl->save($testGaji);
echo "Tersimpan ke storage: " . storage_path('app/gaji_proses.json') . "\n";
echo "Isi file storage sekarang: " . file_get_contents(storage_path('app/gaji_proses.json')) . "\n\n";

// 3. Test simulasi login Heddy Kelana (NIK 1711254) dan cek Approval
session()->put('simpeg_user', [
    'nik' => '1711254',
    'nama_peg' => 'Heddy Kelana, S.H.',
    'userlevel' => '1',
]);

$apprCtrl = app(ApprovalController::class);
$view = $apprCtrl->index();
$pending = $view->getData()['pending'] ?? [];

echo "Jumlah pending approval untuk NIK 1711254 (Heddy Kelana): " . count($pending) . "\n";
foreach ($pending as $p) {
    echo "  - [{$p['jenis']}] NIK: {$p['nik']} | Nama: {$p['nama']} | Gaji Bersih: Rp " . number_format($p['gaji_bersih'], 0, ',', '.') . " | Status: {$p['status']}\n";
}

echo "\n=== TEST SELESAI ===\n";
