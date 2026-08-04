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
     * sebagai 3 laporan terpisah (Slip / Buku Besar / Buku Besar Per Sub),
     * masing-masing tetap bisa toggle sumber data (Gaji 13 / Gaji Bulanan).
     */
    protected function ambilData(Request $request): array
    {
        $sumber = $request->get('sumber', 'gaji13');
        $tahun = (int) $request->get('tahun', now()->year);
        $bulan = null;

        if ($sumber === 'gaji_bulanan') {
            $bulan = (int) $request->get('bulan', now()->month);

            $data = collect(session('dummy_gaji_proses', []))
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->filter(fn ($row) => $row['status'] === 'terbit');
        } else {
            $data = collect(session('dummy_gaji13', []))
                ->where('tahun', $tahun)
                ->filter(fn ($row) => $row['status'] === 'terbit');
        }

        $pegawaiList = session('dummy_pegawai', []);
        $data = $data->map(function ($row) use ($pegawaiList) {
            $p = collect($pegawaiList)->firstWhere('id', $row['pegawai_id']);
            $row['unit_kerja'] = $p['unit_kerja'] ?? '-';

            return $row;
        });

        return compact('data', 'sumber', 'tahun', 'bulan');
    }

    protected function nominalKey(string $sumber): string
    {
        return $sumber === 'gaji_bulanan' ? 'gaji_bersih' : 'gaji13_diterima';
    }

    public function laporanSlip(Request $request)
    {
        $ctx = $this->ambilData($request);
        $userLogin = session('simpeg_user');

        $data = $ctx['data'];
        if ($userLogin['userlevel'] === '5') {
            $data = $data->where('nik', $userLogin['nik']);
        }

        $data = $data->sortBy('nama')->values();
        $sumber = $ctx['sumber'];
        $tahun = $ctx['tahun'];
        $bulan = $ctx['bulan'];
        $nominalKey = $this->nominalKey($sumber);
        $bulanList = AbsensiController::BULAN;

        return view('insentif.laporan-slip', compact('data', 'sumber', 'tahun', 'bulan', 'nominalKey', 'bulanList'));
    }

    public function laporanBukuBesar(Request $request)
    {
        $ctx = $this->ambilData($request);
        $nominalKey = $this->nominalKey($ctx['sumber']);
        $ctx['data'] = $ctx['data']->sortBy('nama')->values();
        $ctx['nominalKey'] = $nominalKey;
        $ctx['total'] = $ctx['data']->sum($nominalKey);
        $ctx['bulanList'] = AbsensiController::BULAN;

        return view('insentif.laporan-buku-besar', $ctx);
    }

    public function laporanBukuBesarPerSub(Request $request)
    {
        $ctx = $this->ambilData($request);
        $nominalKey = $this->nominalKey($ctx['sumber']);
        $ctx['nominalKey'] = $nominalKey;
        $ctx['bulanList'] = AbsensiController::BULAN;
        $ctx['data'] = $ctx['data']->groupBy('unit_kerja')->map(fn ($group) => [
            'rows' => $group->sortBy('nama')->values(),
            'total' => $group->sum($nominalKey),
        ]);

        return view('insentif.laporan-buku-besar-per-sub', $ctx);
    }
}
