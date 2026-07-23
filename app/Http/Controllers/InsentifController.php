<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InsentifController extends Controller
{
    /**
     * MODUL INI READ-ONLY - TIDAK PUNYA DATA SENDIRI.
     *
     * Dicek ke sistem lama: tidak ada file proses_insentif.php atau
     * set_insentif.php sama sekali - hanya file cetak/laporan. Query di
     * dalamnya menunjukkan "Insentif" murni menarik data dari 2 sumber:
     *   - cetak_slip_insentif.php           -> tbl_tigabelas_detail (Gaji 13)
     *   - cetak_slip_insentif_pegawai_permen.php -> tbl_gaji_detail (Gaji Proses Bulanan)
     *
     * Jadi di sini TIDAK dibuat CRUD baru - cukup gabungkan data yang sudah
     * ada dari GajiProsesController & GajiTigabelasController, ditampilkan
     * sebagai laporan. Ini menghindari duplikasi data yang sama dua kali.
     */
    public function index(Request $request)
    {
        $sumber = $request->get('sumber', 'gaji13');
        $tahun = (int) $request->get('tahun', now()->year);

        if ($sumber === 'gaji_bulanan') {
            $bulan = (int) $request->get('bulan', now()->month);

            $data = collect(session('dummy_gaji_proses', []))
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->filter(fn ($row) => $row['status'] === 'terbit')
                ->sortBy('nama')
                ->values();

            return view('insentif.index', [
                'data' => $data,
                'sumber' => $sumber,
                'tahun' => $tahun,
                'bulan' => $bulan,
                'bulanList' => AbsensiController::BULAN,
            ]);
        }

        $data = collect(session('dummy_gaji13', []))
            ->where('tahun', $tahun)
            ->filter(fn ($row) => $row['status'] === 'terbit')
            ->sortBy('nama')
            ->values();

        return view('insentif.index', [
            'data' => $data,
            'sumber' => $sumber,
            'tahun' => $tahun,
            'bulan' => null,
            'bulanList' => AbsensiController::BULAN,
        ]);
    }
}