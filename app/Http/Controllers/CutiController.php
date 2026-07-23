<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CutiController extends Controller
{
    /**
     * MODUL INI READ-ONLY - TIDAK PUNYA DATA SENDIRI.
     *
     * Dicek ke sistem lama: tidak ada file proses_cuti.php atau tambah_cuti.php
     * sama sekali - hanya file cetak/laporan (cetak_cuti_per_pegawai.php,
     * laporan_cuti_pegawai.php). Query di dalamnya:
     *   SELECT * FROM tbl_prestasi WHERE cuti >= 1 AND YEAR(tgl_prestasi)='$tahun'
     *
     * Jadi "Cuti" murni laporan yang menyaring data dari modul Prestasi
     * (field 'cuti' dan 'alasan_cuti' yang sudah ada di PrestasiController) -
     * TIDAK dibuat CRUD baru, supaya tidak duplikasi data yang sama.
     */
    protected function pegawaiById(int $id): ?array
    {
        return collect(session('dummy_pegawai', []))->firstWhere('id', $id);
    }

    public function index(Request $request)
    {
        $tahun = (int) $request->get('tahun', now()->year);

        $cuti = collect(session('dummy_prestasi_gaji', []))
            ->filter(fn ($row) => $row['cuti'] > 0 && \Illuminate\Support\Carbon::parse($row['tanggal'])->year === $tahun)
            ->map(function ($row) {
                $p = $this->pegawaiById($row['pegawai_id']);
                $row['nik'] = $p['nik'] ?? '-';
                $row['nama'] = $p['nama'] ?? '(pegawai tidak ditemukan)';
                $row['unit_kerja'] = $p['unit_kerja'] ?? '-';

                return $row;
            })
            ->sortBy('nama')
            ->values();

        return view('cuti.index', compact('cuti', 'tahun'));
    }
}
