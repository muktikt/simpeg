<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$pegawai = DB::table('pegawai')->where('nik', '1711254')->first();

echo "Testing insert to payroll table for pegawai: {$pegawai->name} ({$pegawai->id})\n";

try {
    DB::table('payroll')->updateOrInsert(
        [
            'pegawai_id' => $pegawai->id,
            'periode' => 'September 2026',
        ],
        [
            'tahun' => 2026,
            'bulan' => 9,
            'status' => 'DITERBITKAN',
            'gapok' => 4500000,
            'tunjangan_istri' => 450000,
            'tunjangan_anak' => 200000,
            'tunjangan_jabatan' => 1200000,
            'tunjangan_prestasi' => 0,
            'tunjangan_transportasi' => 300000,
            'tunjangan_pangan' => 250000,
            'tunjangan_bpjs_kesehatan' => 150000,
            'tunjangan_perumahan' => 500000,
            'tunjangan_bpjs_tenaga_kerja' => 120000,
            'tunjangan_perusahaan' => 0,
            'lembur' => 0,
            'tunjangan_pajak' => 0,
            'tunjangan_air_minum' => 0,
            'tunjangan_komunikasi' => 0,
            'potongan_sanksi_perusahaan' => 0,
            'potongan_trandist_pmi_lain' => 0,
            'potongan_dapenma' => 200000,
            'potongan_bpjs_tenaga_kerja' => 120000,
            'potongan_perumahan' => 0,
            'potongan_tunjangan_perusahaan' => 0,
            'potongan_korpri' => 20000,
            'potongan_pajak' => 50000,
            'potongan_bpjs_kesehatan' => 150000,
            'potongan_koperasi' => 100000,
            'potongan_darma_wanita' => 0,
            'potongan_rekening_air_minum' => 0,
            'potongan_kas' => 0,
            'potongan_bank_bjb' => 0,
            'potongan_bank_bjbs' => 0,
            'potongan_bank_btn' => 0,
            'potongan_bank_bpr' => 0,
            'potongan_asuransi' => 0,
            'potongan_zakat_profesi' => 0,
        ]
    );
    echo "SUCCESS! Inserted/Updated payroll row successfully.\n";
    
    $row = DB::table('payroll')->where('pegawai_id', $pegawai->id)->first();
    echo "Fetched row ID: {$row->id} | Periode: {$row->periode} | Gapok: {$row->gapok} | Status: {$row->status}\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
