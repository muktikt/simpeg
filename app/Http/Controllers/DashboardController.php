<?php

namespace App\Http\Controllers;

class DashboardController extends Controller
{
    /**
     * DATA DUMMY - menggantikan sementara query asli di beranda.php:
     *   select count(*) from tbl_pegawai where status_peg = 'PT' / 'DI' / 'CP' / 'PH' / 'PN' / 'TK'
     * Ganti $komposisi di bawah ini dengan hasil query beneran kalau sudah
     * siap dihubungkan ke database.
     */
    public function index()
    {
        $komposisi = [
            'direksi' => 5,
            'pegawai_tetap' => 612,
            'calon_pegawai' => 48,
            'honorer' => 310,
            'tenaga_kontrak' => 225,
            'pensiun' => 40,
        ];

        $total = array_sum($komposisi);

        $stats = [
            'cuti_pending' => 14,
            'absensi_hari_ini' => 1186,
            'periode_gaji_berjalan' => 'Juli 2026',
            'jumlah_unit_kerja' => 18,
        ];

        $aktivitas = [
            ['nama' => 'Rudi Hartono', 'nik' => '1711298', 'jam' => '08:02'],
            ['nama' => 'Siti Aminah', 'nik' => '1809441', 'jam' => '07:54'],
            ['nama' => 'Bagas Nugroho', 'nik' => '1920113', 'jam' => '07:41'],
            ['nama' => 'Wahyu Setiawan', 'nik' => '1711254', 'jam' => '07:30'],
        ];

        return view('dashboard', compact('komposisi', 'total', 'stats', 'aktivitas'));
    }
}
