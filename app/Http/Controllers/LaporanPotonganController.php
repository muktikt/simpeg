<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LaporanPotonganController extends Controller
{
    /**
     * Laporan Potongan Keuangan — menampilkan data potongan dengan filter bulan/tahun.
     * Disamakan dengan laporan_keu.php, laporan_keu_minus.php, laporan_keu_non_minus.php,
     * laporan_bpjs.php, laporan_keu_thr.php, dsb.
     */

    protected array $kolom = [
        'pot_koperasi', 'pot_darmawanita', 'pot_air', 'pot_kas',
        'pot_bjb', 'pot_bjbs', 'pot_asuransi', 'pot_btn',
        'pot_zakat_profesi', 'pot_bpjs', 'pot_bpr',
    ];

    protected array $kolomLabels = [
        'pot_koperasi'      => 'Koperasi',
        'pot_darmawanita'   => 'Darmawanita',
        'pot_air'           => 'Ledeng',
        'pot_kas'           => 'KAS',
        'pot_bjb'           => 'BJB',
        'pot_bjbs'          => 'BJBS',
        'pot_asuransi'      => 'Asuransi',
        'pot_btn'           => 'BTN',
        'pot_zakat_profesi' => 'Zakat Profesi',
        'pot_bpjs'          => 'BPJS',
        'pot_bpr'           => 'BPR',
    ];

    protected function getData(string $tipe, ?int $bulan = null, ?int $tahun = null): \Illuminate\Support\Collection
    {
        $key = "dummy_potongan_{$tipe}";
        $data = collect(session($key, []));
        $pegawai = collect(app(PegawaiController::class)->all());

        $bulan = $bulan ?? now()->month;
        $tahun = $tahun ?? now()->year;

        return $data->filter(function ($r) use ($bulan, $tahun) {
            $d = \Carbon\Carbon::parse($r['tgl_potongan']);
            return $d->month === $bulan && $d->year === $tahun;
        })->map(function ($r) use ($pegawai) {
            $p = $pegawai->firstWhere('nik', $r['nik']);
            $r['nama'] = $p['nama'] ?? '(tidak ditemukan)';
            $total = 0;
            foreach ($this->kolom as $k) {
                $total += $r[$k] ?? 0;
            }
            $r['total'] = $total;
            return $r;
        })->values();
    }

    // Lap. Potongan Keu (semua)
    public function potonganKeu(Request $request)
    {
        $bulan = (int) ($request->bulan ?? now()->month);
        $tahun = (int) ($request->tahun ?? now()->year);
        $items = $this->getData('gaji', $bulan, $tahun);

        return view('laporan-potongan.potongan-keu', [
            'items' => $items, 'bulan' => $bulan, 'tahun' => $tahun,
            'kolom' => $this->kolom, 'kolomLabels' => $this->kolomLabels,
            'judul' => 'Laporan Potongan Keuangan',
        ]);
    }

    // Lap. Potongan Keu Minus (total potongan > pendapatan)
    public function potonganKeuMinus(Request $request)
    {
        $bulan = (int) ($request->bulan ?? now()->month);
        $tahun = (int) ($request->tahun ?? now()->year);
        $items = $this->getData('gaji', $bulan, $tahun)->filter(fn ($r) => ($r['total'] ?? 0) < 0);

        return view('laporan-potongan.potongan-keu', [
            'items' => $items, 'bulan' => $bulan, 'tahun' => $tahun,
            'kolom' => $this->kolom, 'kolomLabels' => $this->kolomLabels,
            'judul' => 'Laporan Potongan Keuangan (Minus)',
        ]);
    }

    // Lap. Potongan Keu Non-Minus
    public function potonganKeuNonMinus(Request $request)
    {
        $bulan = (int) ($request->bulan ?? now()->month);
        $tahun = (int) ($request->tahun ?? now()->year);
        $items = $this->getData('gaji', $bulan, $tahun)->filter(fn ($r) => ($r['total'] ?? 0) >= 0);

        return view('laporan-potongan.potongan-keu', [
            'items' => $items, 'bulan' => $bulan, 'tahun' => $tahun,
            'kolom' => $this->kolom, 'kolomLabels' => $this->kolomLabels,
            'judul' => 'Laporan Potongan Keuangan (Non-Minus)',
        ]);
    }

    // Lap. Potongan BPJS
    public function potonganBpjs(Request $request)
    {
        $bulan = (int) ($request->bulan ?? now()->month);
        $tahun = (int) ($request->tahun ?? now()->year);
        $items = $this->getData('gaji', $bulan, $tahun);

        return view('laporan-potongan.potongan-bpjs', [
            'items' => $items, 'bulan' => $bulan, 'tahun' => $tahun,
        ]);
    }

    // Lap. Potongan THR
    public function potonganThr(Request $request)
    {
        $bulan = (int) ($request->bulan ?? now()->month);
        $tahun = (int) ($request->tahun ?? now()->year);
        $items = $this->getData('thr', $bulan, $tahun);

        return view('laporan-potongan.potongan-keu', [
            'items' => $items, 'bulan' => $bulan, 'tahun' => $tahun,
            'kolom' => $this->kolom, 'kolomLabels' => $this->kolomLabels,
            'judul' => 'Laporan Potongan Keuangan THR',
        ]);
    }

    // Lap. Payroll THR
    public function payrollThr(Request $request)
    {
        $bulan = (int) ($request->bulan ?? now()->month);
        $tahun = (int) ($request->tahun ?? now()->year);
        $items = $this->getData('thr', $bulan, $tahun);

        return view('laporan-potongan.payroll', [
            'items' => $items, 'bulan' => $bulan, 'tahun' => $tahun,
            'judul' => 'Laporan Payroll THR',
        ]);
    }

    // Lap. Pajak THR
    public function pajakThr(Request $request)
    {
        $bulan = (int) ($request->bulan ?? now()->month);
        $tahun = (int) ($request->tahun ?? now()->year);
        $items = $this->getData('thr', $bulan, $tahun);

        return view('laporan-potongan.pajak', [
            'items' => $items, 'bulan' => $bulan, 'tahun' => $tahun,
            'judul' => 'Laporan Pajak THR',
        ]);
    }

    // Lap. Payroll Gaji 13
    public function payrollGaji13(Request $request)
    {
        $bulan = (int) ($request->bulan ?? now()->month);
        $tahun = (int) ($request->tahun ?? now()->year);
        $items = $this->getData('gaji13', $bulan, $tahun);

        return view('laporan-potongan.payroll', [
            'items' => $items, 'bulan' => $bulan, 'tahun' => $tahun,
            'judul' => 'Laporan Payroll Gaji 13',
        ]);
    }

    // Lap. Pajak Gaji 13
    public function pajakGaji13(Request $request)
    {
        $bulan = (int) ($request->bulan ?? now()->month);
        $tahun = (int) ($request->tahun ?? now()->year);
        $items = $this->getData('gaji13', $bulan, $tahun);

        return view('laporan-potongan.pajak', [
            'items' => $items, 'bulan' => $bulan, 'tahun' => $tahun,
            'judul' => 'Laporan Pajak Gaji 13',
        ]);
    }
}
