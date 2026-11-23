<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\PejabatTtdController;
use Carbon\Carbon;

class LaporanPotonganController extends Controller
{
    /**
     * Modul Laporan Potongan Keuangan & Deteksi Gaji Minus (Role 2 Keuangan & Role 1 SDM).
     *
     * Disamakan dengan sistem lama:
     * - laporan_keu.php (Laporan Potongan Keuangan Rekap Keseluruhan)
     * - laporan_keu_minus.php / cetak_laporan_keu_minus.php / cetak_laporan_keu_minus_all.php
     * - laporan_keu_non_minus.php / cetak_laporan_keu_non_minus.php
     * - cetak_laporan_payroll_minus.php (Laporan Rekapitulasi Gaji Minus Bank BJB)
     * - cek_jumlah_detail_minus.php
     */

    protected array $kolom = [
        'pot_koperasi', 'pot_darmawanita', 'pot_air', 'pot_kas',
        'pot_bjb', 'pot_bjbs', 'pot_asuransi', 'pot_btn',
        'pot_zakat_profesi', 'pot_bpjs', 'pot_bpr',
    ];

    protected array $kolomLabels = [
        'pot_koperasi'      => 'Koperasi',
        'pot_darmawanita'   => 'D. Wanita',
        'pot_air'           => 'Air/Ledeng',
        'pot_kas'           => 'KAS',
        'pot_bjb'           => 'BJB',
        'pot_bjbs'          => 'BJB Syariah',
        'pot_asuransi'      => 'Asuransi',
        'pot_btn'           => 'BTN',
        'pot_zakat_profesi' => 'Zakat',
        'pot_bpjs'          => 'BPJS',
        'pot_bpr'           => 'BRI / BPR',
    ];

    /**
     * Daftar Unit Kerja aktif untuk filter.
     */
    protected function getAllUnitKerja(): array
    {
        try {
            return DB::table('pegawai')
                ->whereNotNull('unit_kerja')
                ->where('unit_kerja', '!=', '')
                ->distinct()
                ->orderBy('unit_kerja')
                ->pluck('unit_kerja')
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Ambil data potongan lengkap diperkaya dengan data Pegawai, Payroll, dan status Minus/Non-Minus.
     */
    protected function getEnrichedData(string $tipe, int $bulan, int $tahun, ?string $unitKerja = null): \Illuminate\Support\Collection
    {
        // 1. Rekening BJB Map
        $rekMap = [];
        try {
            $rekJson = storage_path('app/rek_bjb.json');
            if (file_exists($rekJson)) {
                $reks = json_decode(file_get_contents($rekJson), true) ?: [];
                foreach ($reks as $r) {
                    if (!empty($r['nik'])) {
                        $rekMap[$r['nik']] = $r['no_rek'] ?? $r['no_rekening'] ?? '-';
                    }
                }
            }
        } catch (\Throwable $e) {}

        // 2. Pegawai Map
        $pegawaiMap = collect(app(PegawaiController::class)->all())->keyBy('nik');

        // 3. Payroll Map bulan berjalan
        $payrollMap = collect();
        try {
            $payrollMap = DB::table('payroll')
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->get()
                ->keyBy('pegawai_id');
        } catch (\Throwable $e) {}

        // Baseline Income Map (dari payroll riwayat terdekat untuk estimasi pendapatan pegawai)
        $baselineIncomeMap = collect();
        try {
            $baselineIncomeMap = DB::table('payroll')
                ->select('pegawai_id', 'total_pendapatan', 'total_potongan', 'gaji_bersih')
                ->orderByDesc('tahun')
                ->orderByDesc('bulan')
                ->get()
                ->unique('pegawai_id')
                ->keyBy('pegawai_id');
        } catch (\Throwable $e) {}

        // 4. Query potongan_keu
        try {
            $potonganRows = DB::table('potongan_keu')
                ->where('tipe', $tipe)
                ->where(function ($q) use ($bulan, $tahun) {
                    $q->where(function ($sub) use ($bulan, $tahun) {
                        $sub->where('bulan', $bulan)
                            ->where('tahun', $tahun);
                    })->orWhere(function ($sub) use ($bulan, $tahun) {
                        $sub->whereNull('bulan')
                            ->whereYear('created_at', $tahun)
                            ->whereMonth('created_at', $bulan);
                    });
                })
                ->get();
        } catch (\Throwable $e) {
            $potonganRows = collect();
        }

        $items = $potonganRows->map(function ($r) use ($pegawaiMap, $payrollMap, $baselineIncomeMap, $rekMap) {
            $r = (array) $r;
            $nik = trim((string)($r['nik'] ?? ''));
            $peg = $pegawaiMap->get($nik, []);
            $pegDbId = $peg['id'] ?? $peg['db_id'] ?? null;

            $namaPeg = trim(($peg['nama'] ?? $r['nama_pegawai'] ?? 'Pegawai') . (!empty($peg['gelar']) ? ', ' . $peg['gelar'] : ''));
            $unit = $peg['unit_kerja'] ?? 'Kantor Pusat';
            $jabatan = $peg['jabatan'] ?? '-';
            $noRek = $rekMap[$nik] ?? ($peg['no_rek'] ?? ($peg['rekening'] ?? '-'));

            // Hitung total dari 11 kolom potongan keuangan
            $totPotKeu = 0;
            foreach ($this->kolom as $k) {
                $totPotKeu += (float) ($r[$k] ?? 0);
            }
            $r['total_potongan_keu'] = $totPotKeu;
            $r['total'] = $totPotKeu;

            // Cek data payroll
            $pay = $payrollMap->get($pegDbId) ?: $baselineIncomeMap->get($pegDbId);

            $pendapatanKotor = $pay ? (float)$pay->total_pendapatan : 4500000;
            $potWajib = $pay ? (float)$pay->total_potongan : 0;
            $pendapatanDiterima = max(0, $pendapatanKotor - $potWajib);

            if ($pay && $pay->gaji_bersih !== null && $pay->gaji_bersih > 0) {
                $gajiDiterima = (float)$pay->gaji_bersih;
            } else {
                $gajiDiterima = $pendapatanDiterima - $totPotKeu;
            }

            // Pegawai terdeteksi MINUS jika sisa gaji diterima <= 0 atau potongan keu >= pendapatan
            $isMinus = ($gajiDiterima <= 0) || ($pendapatanDiterima > 0 && $totPotKeu >= $pendapatanDiterima);
            $defisit = $isMinus ? abs(min(0, $gajiDiterima)) : 0;

            $r['nama'] = $namaPeg;
            $r['unit_kerja'] = $unit;
            $r['jabatan'] = $jabatan;
            $r['no_rek'] = $noRek;
            $r['pendapatan_kotor'] = $pendapatanKotor;
            $r['potongan_wajib'] = $potWajib;
            $r['pendapatan_diterima'] = $pendapatanDiterima;
            $r['gaji_diterima'] = $gajiDiterima;
            $r['is_minus'] = $isMinus;
            $r['defisit'] = $defisit;

            return $r;
        });

        // Filter unit kerja jika dipilih spesifik
        if (!empty($unitKerja) && $unitKerja !== 'all') {
            $items = $items->filter(fn ($r) => strcasecmp(trim($r['unit_kerja'] ?? ''), trim($unitKerja)) === 0)->values();
        }

        return $items;
    }

    /**
     * 1. Laporan Potongan Keuangan (Semua Pegawai)
     */
    public function potonganKeu(Request $request)
    {
        $bulan = (int) ($request->bulan ?? now()->month);
        $tahun = (int) ($request->tahun ?? now()->year);
        $unitKerja = $request->get('unit_kerja', 'all');

        $items = $this->getEnrichedData('gaji', $bulan, $tahun, $unitKerja);
        $unitKerjaList = $this->getAllUnitKerja();
        $pejabatTtd = PejabatTtdController::getTtd('keuangan');

        return view('laporan-potongan.potongan-keu', [
            'items'         => $items,
            'bulan'         => $bulan,
            'tahun'         => $tahun,
            'unitKerja'     => $unitKerja,
            'unitKerjaList' => $unitKerjaList,
            'kolom'         => $this->kolom,
            'kolomLabels'   => $this->kolomLabels,
            'pejabatTtd'    => $pejabatTtd,
            'judul'         => 'Laporan Potongan Keuangan',
        ]);
    }

    /**
     * 2. Laporan Khusus Deteksi Potongan & Gaji Minus (Gaji Diterima <= 0 / Kurang Potong)
     */
    public function potonganKeuMinus(Request $request)
    {
        $bulan = (int) ($request->bulan ?? now()->month);
        $tahun = (int) ($request->tahun ?? now()->year);
        $unitKerja = $request->get('unit_kerja', 'all');

        $allItems = $this->getEnrichedData('gaji', $bulan, $tahun, $unitKerja);
        $items = $allItems->filter(fn ($r) => $r['is_minus'])->values();

        // Rangkuman Statistik Defisit
        $totalPegawaiMinus = count($items);
        $totalPotonganMinus = $items->sum('total_potongan_keu');
        $totalDefisit = $items->sum('defisit');

        $unitKerjaList = $this->getAllUnitKerja();
        $pejabatTtd = PejabatTtdController::getTtd('keuangan');

        return view('laporan-potongan.minus', [
            'items'              => $items,
            'bulan'              => $bulan,
            'tahun'              => $tahun,
            'unitKerja'          => $unitKerja,
            'unitKerjaList'      => $unitKerjaList,
            'kolom'              => $this->kolom,
            'kolomLabels'        => $this->kolomLabels,
            'totalPegawaiMinus'  => $totalPegawaiMinus,
            'totalPotonganMinus' => $totalPotonganMinus,
            'totalDefisit'       => $totalDefisit,
            'pejabatTtd'         => $pejabatTtd,
            'judul'              => 'Laporan Khusus Deteksi Potongan & Gaji Minus',
        ]);
    }

    /**
     * 3. Laporan Potongan Keuangan Non-Minus (Normal > 0)
     */
    public function potonganKeuNonMinus(Request $request)
    {
        $bulan = (int) ($request->bulan ?? now()->month);
        $tahun = (int) ($request->tahun ?? now()->year);
        $unitKerja = $request->get('unit_kerja', 'all');

        $allItems = $this->getEnrichedData('gaji', $bulan, $tahun, $unitKerja);
        $items = $allItems->filter(fn ($r) => !$r['is_minus'])->values();

        $totalPegawai = count($items);
        $totalPotongan = $items->sum('total_potongan_keu');
        $totalGajiDiterima = $items->sum('gaji_diterima');

        $unitKerjaList = $this->getAllUnitKerja();
        $pejabatTtd = PejabatTtdController::getTtd('keuangan');

        return view('laporan-potongan.non-minus', [
            'items'             => $items,
            'bulan'             => $bulan,
            'tahun'             => $tahun,
            'unitKerja'         => $unitKerja,
            'unitKerjaList'     => $unitKerjaList,
            'kolom'             => $this->kolom,
            'kolomLabels'       => $this->kolomLabels,
            'totalPegawai'      => $totalPegawai,
            'totalPotongan'     => $totalPotongan,
            'totalGajiDiterima' => $totalGajiDiterima,
            'pejabatTtd'        => $pejabatTtd,
            'judul'             => 'Laporan Potongan Keuangan (Non-Minus)',
        ]);
    }

    /**
     * Cetak Fisik Kedinasan Laporan Potongan Kas (Minus)
     * Format cetak resmi landscape A4 sesuai cetak_laporan_keu_minus.php / cetak_laporan_keu_minus_all.php
     */
    public function cetakMinus(Request $request)
    {
        $bulan = (int) ($request->bulan ?? now()->month);
        $tahun = (int) ($request->tahun ?? now()->year);
        $unitKerja = $request->get('unit_kerja', 'all');

        $allItems = $this->getEnrichedData('gaji', $bulan, $tahun, $unitKerja);
        $items = $allItems->filter(fn ($r) => $r['is_minus'])->values();

        $totalPegawaiMinus = count($items);
        $totalPotonganMinus = $items->sum('total_potongan_keu');
        $totalDefisit = $items->sum('defisit');

        $pejabatTtd = PejabatTtdController::getTtd('keuangan');
        $namaBulan = \App\Http\Controllers\AbsensiController::BULAN[$bulan] ?? 'Bulan ' . $bulan;

        return view('laporan-potongan.cetak-minus', [
            'items'              => $items,
            'bulan'              => $bulan,
            'tahun'              => $tahun,
            'namaBulan'          => $namaBulan,
            'unitKerja'          => $unitKerja,
            'kolom'              => $this->kolom,
            'kolomLabels'        => $this->kolomLabels,
            'totalPegawaiMinus'  => $totalPegawaiMinus,
            'totalPotonganMinus' => $totalPotonganMinus,
            'totalDefisit'       => $totalDefisit,
            'pejabatTtd'         => $pejabatTtd,
        ]);
    }

    /**
     * Cetak Fisik Laporan Gaji Bank BJB (Payroll Minus)
     * Format resmi landscape A4 sesuai cetak_laporan_payroll_minus.php
     */
    public function cetakPayrollMinus(Request $request)
    {
        $bulan = (int) ($request->bulan ?? now()->month);
        $tahun = (int) ($request->tahun ?? now()->year);
        $unitKerja = $request->get('unit_kerja', 'all');

        $allItems = $this->getEnrichedData('gaji', $bulan, $tahun, $unitKerja);
        $items = $allItems->filter(fn ($r) => $r['is_minus'])->values();

        $pejabatTtd = PejabatTtdController::getTtd('keuangan');
        $namaBulan = \App\Http\Controllers\AbsensiController::BULAN[$bulan] ?? 'Bulan ' . $bulan;

        return view('laporan-potongan.cetak-payroll-minus', [
            'items'      => $items,
            'bulan'      => $bulan,
            'tahun'      => $tahun,
            'namaBulan'  => $namaBulan,
            'unitKerja'  => $unitKerja,
            'pejabatTtd' => $pejabatTtd,
        ]);
    }

    /**
     * Cetak Fisik Laporan Potongan Keuangan Non-Minus
     */
    public function cetakNonMinus(Request $request)
    {
        $bulan = (int) ($request->bulan ?? now()->month);
        $tahun = (int) ($request->tahun ?? now()->year);
        $unitKerja = $request->get('unit_kerja', 'all');

        $allItems = $this->getEnrichedData('gaji', $bulan, $tahun, $unitKerja);
        $items = $allItems->filter(fn ($r) => !$r['is_minus'])->values();

        $pejabatTtd = PejabatTtdController::getTtd('keuangan');
        $namaBulan = \App\Http\Controllers\AbsensiController::BULAN[$bulan] ?? 'Bulan ' . $bulan;

        return view('laporan-potongan.cetak-non-minus', [
            'items'      => $items,
            'bulan'      => $bulan,
            'tahun'      => $tahun,
            'namaBulan'  => $namaBulan,
            'unitKerja'  => $unitKerja,
            'kolom'      => $this->kolom,
            'kolomLabels'=> $this->kolomLabels,
            'pejabatTtd' => $pejabatTtd,
        ]);
    }

    // Lap. Potongan BPJS
    public function potonganBpjs(Request $request)
    {
        $bulan = (int) ($request->bulan ?? now()->month);
        $tahun = (int) ($request->tahun ?? now()->year);
        $items = $this->getEnrichedData('gaji', $bulan, $tahun);

        return view('laporan-potongan.potongan-bpjs', [
            'items' => $items, 'bulan' => $bulan, 'tahun' => $tahun,
        ]);
    }

    // Lap. Potongan THR
    public function potonganThr(Request $request)
    {
        $bulan = (int) ($request->bulan ?? now()->month);
        $tahun = (int) ($request->tahun ?? now()->year);
        $items = $this->getEnrichedData('thr', $bulan, $tahun);

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
        $items = $this->getEnrichedData('thr', $bulan, $tahun);

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
        $items = $this->getEnrichedData('thr', $bulan, $tahun);

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
        $items = $this->getEnrichedData('gaji13', $bulan, $tahun);

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
        $items = $this->getEnrichedData('gaji13', $bulan, $tahun);

        return view('laporan-potongan.pajak', [
            'items' => $items, 'bulan' => $bulan, 'tahun' => $tahun,
            'judul' => 'Laporan Pajak Gaji 13',
        ]);
    }
}
