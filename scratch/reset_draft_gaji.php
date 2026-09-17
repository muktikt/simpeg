<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\GajiProsesController;

$gajiCtrl = app(GajiProsesController::class);

$draftData = [
    [
        'id' => 1,
        'pegawai_id' => 1,
        'nik' => '1711252',
        'nama' => 'Intan Wulansari',
        'jabatan' => 'Asisten Manajer Administrasi Sumber Daya Manusia',
        'unit_kerja' => 'Kantor Pusat',
        'kategori' => 'satuan',
        'kode_ptkp' => 'K1',
        'bulan' => (int) date('n'),
        'tahun' => (int) date('Y'),
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

$gajiCtrl->save($draftData);
echo "Draft gaji periode " . date('M Y') . " berhasil disiapkan untuk pengujian.\n";
