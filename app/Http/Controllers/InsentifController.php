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
        $sumber = $request->get('sumber', 'gaji_bulanan');
        $tahun = (int) $request->get('tahun', now()->year);
        $bulan = (int) $request->get('bulan', now()->month);

        if ($sumber === 'gaji_bulanan') {
            // Ambil dari GajiProsesController yang membaca database Supabase tabel 'payroll'
            $allGaji = app(GajiProsesController::class)->all();
            if (empty($allGaji)) {
                $allGaji = session('dummy_gaji_proses', []);
            }

            $data = collect($allGaji)
                ->filter(function ($row) use ($bulan, $tahun) {
                    $mMatch = ((int) ($row['bulan'] ?? 0)) === $bulan;
                    $yMatch = ((int) ($row['tahun'] ?? 0)) === $tahun;
                    $status = strtolower($row['status'] ?? '');
                    $statusMatch = in_array($status, ['terbit', 'diterbitkan'], true);

                    return $mMatch && $yMatch && $statusMatch;
                });
        } else {
            // Ambil dari GajiTigabelasController yang membaca tabel gaji_13 / payroll
            $allGaji13 = app(GajiTigabelasController::class)->all();
            if (empty($allGaji13)) {
                $allGaji13 = session('dummy_gaji13', []);
            }

            $data = collect($allGaji13)
                ->filter(function ($row) use ($tahun) {
                    $yMatch = ((int) ($row['tahun'] ?? 0)) === $tahun;
                    $status = strtolower($row['status'] ?? '');
                    return $yMatch && in_array($status, ['terbit', 'diterbitkan'], true);
                });
        }

        $pegawaiList = app(PegawaiController::class)->all();
        $data = $data->map(function ($row) use ($pegawaiList) {
            $p = collect($pegawaiList)->first(function ($item) use ($row) {
                return (!empty($row['pegawai_id']) && (($item['id'] ?? null) == $row['pegawai_id'] || ($item['db_id'] ?? null) == $row['pegawai_id']))
                    || (!empty($row['nik']) && ($item['nik'] ?? null) == $row['nik']);
            });

            $row['unit_kerja'] = $p['unit_kerja'] ?? ($row['unit_kerja'] ?? '-');
            $row['nama'] = $row['nama'] ?? ($p['nama'] ?? 'Pegawai');
            $row['nik'] = $row['nik'] ?? ($p['nik'] ?? '-');

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
        $riwayatInsentif = [];

        if ($userLogin['userlevel'] === '5' || $request->has('my')) {
            $myNik = $userLogin['nik'] ?? '';
            $data = $data->filter(fn ($r) => ($r['nik'] ?? '') === $myNik)->values();

            // Ambil seluruh riwayat gaji & lembur yang sudah diterbitkan untuk pegawai ini
            $allMyPayrolls = collect(app(GajiProsesController::class)->all())
                ->filter(function ($row) use ($myNik) {
                    $status = strtolower($row['status'] ?? '');
                    return in_array($status, ['terbit', 'diterbitkan'], true)
                        && (($row['nik'] ?? '') === $myNik);
                })
                ->sortByDesc(fn ($r) => ((int) ($r['tahun'] ?? 0)) * 100 + ((int) ($r['bulan'] ?? 0)))
                ->values();

            $riwayatInsentif = [];
            foreach ($allMyPayrolls as $pRow) {
                $pBulan = AbsensiController::BULAN[(int) ($pRow['bulan'] ?? 0)] ?? ('Bulan ' . ($pRow['bulan'] ?? ''));
                $pTahun = $pRow['tahun'] ?? now()->year;
                $pNominal = (int) ($pRow['gaji_bersih'] ?? $pRow['total_pendapatan'] ?? 0);
                $pLembur = (int) ($pRow['lembur'] ?? 0);

                $desc = "$pBulan $pTahun";
                if ($pLembur > 0) {
                    $desc .= " &middot; Termasuk Lembur Rp " . number_format($pLembur, 0, ',', '.');
                }

                $riwayatInsentif[] = [
                    'judul' => 'Insentif & Payroll ' . $pBulan,
                    'periode' => $desc,
                    'nominal' => $pNominal,
                    'lembur' => $pLembur,
                    'icon' => '⭐',
                ];
            }

            if (empty($riwayatInsentif)) {
                $riwayatInsentif = [
                    ['judul' => 'Insentif Kinerja', 'periode' => 'Belum ada payroll diterbitkan', 'nominal' => 0, 'lembur' => 0, 'icon' => '🔆'],
                ];
            }
        }

        $data = $data->sortBy('nama')->values();
        $sumber = $ctx['sumber'];
        $tahun = $ctx['tahun'];
        $bulan = $ctx['bulan'];
        $nominalKey = $this->nominalKey($sumber);
        $bulanList = AbsensiController::BULAN;

        return view('insentif.laporan-slip', compact('data', 'sumber', 'tahun', 'bulan', 'nominalKey', 'bulanList', 'riwayatInsentif'));
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
