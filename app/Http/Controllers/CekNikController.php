<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Controllers\PegawaiController;
use Carbon\Carbon;

class CekNikController extends Controller
{
    /**
     * Modul Cek NIK, Komparasi Selisih Potongan (Diff Otomatis),
     * dan Hapus Kesalahan NIK Potongan (Role 2 Keuangan & Role 1 SDM).
     *
     * Fitur Utama:
     * 1. Komparasi Selisih NIK (Diff Otomatis Periode A vs Periode B):
     *    - Pegawai Baru (Hadir di B, tidak ada di A)
     *    - Pegawai Keluar / Pensiun (Hadir di A, tidak ada di B)
     *    - Nominal Berubah (Total B != Total A, rincian per komponen yang berubah)
     *    - Nominal Tetap (Total B == Total A)
     * 2. Cek NIK Bulan Kemarin / Acuan (Lengkap dengan Nama, Unit Kerja, Rincian)
     * 3. Cek NIK Bulan Berjalan / Pembanding (Lengkap dengan Nama, Unit Kerja, Rincian)
     * 4. Hapus Kesalahan NIK (Hapus baris potongan spesifik)
     * 5. Ekspor Hasil Komparasi ke CSV / Excel
     */

    protected array $kolom = [
        'pot_koperasi', 'pot_darmawanita', 'pot_air', 'pot_kas',
        'pot_bjb', 'pot_bjbs', 'pot_asuransi', 'pot_btn',
        'pot_zakat_profesi', 'pot_bpjs', 'pot_bpr',
    ];

    protected array $kolomLabels = [
        'pot_koperasi'      => 'Koperasi',
        'pot_darmawanita'   => 'D. Wanita',
        'pot_air'           => 'Air / Ledeng',
        'pot_kas'           => 'KAS',
        'pot_bjb'           => 'BJB',
        'pot_bjbs'          => 'BJB Syariah',
        'pot_asuransi'      => 'Asuransi',
        'pot_btn'           => 'BTN',
        'pot_zakat_profesi' => 'Zakat Profesi',
        'pot_bpjs'          => 'BPJS',
        'pot_bpr'           => 'BRI / BPR',
    ];

    protected array $tipeLabels = [
        'gaji'   => 'Potongan Gaji',
        'thr'    => 'Potongan THR',
        'gaji13' => 'Potongan Gaji 13',
    ];

    /**
     * Ambil mapping pegawai dari PegawaiController (diindeks berdasarkan string NIK).
     */
    protected function getPegawaiMap(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $map = [];
        try {
            $all = app(PegawaiController::class)->all();
            foreach ($all as $p) {
                $nik = trim((string)($p['nik'] ?? ''));
                if ($nik !== '') {
                    $gelar = trim($p['gelar'] ?? '');
                    $nama = trim($p['nama'] ?? 'Pegawai');
                    $namaLengkap = $gelar !== '' ? "{$nama}, {$gelar}" : $nama;

                    $map[$nik] = [
                        'id'           => $p['id'] ?? null,
                        'db_id'        => $p['db_id'] ?? null,
                        'nik'          => $nik,
                        'nama'         => $nama,
                        'gelar'        => $gelar,
                        'nama_lengkap' => $namaLengkap,
                        'jabatan'      => $p['jabatan'] ?? '-',
                        'unit_kerja'   => $p['unit_kerja'] ?? '-',
                        'status_peg'   => $p['status_peg'] ?? 'PT',
                    ];
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('CekNikController getPegawaiMap: ' . $e->getMessage());
        }

        $cache = $map;
        return $map;
    }

    /**
     * Hitung total dari 11 kolom potongan keuangan.
     */
    protected function hitungTotalPotongan(array|object $row): int
    {
        $r = (array) $row;
        $total = 0;
        foreach ($this->kolom as $col) {
            $total += (int) ($r[$col] ?? 0);
        }
        return $total;
    }

    /**
     * Ambil data potongan untuk suatu tipe, bulan, dan tahun.
     */
    protected function getPotonganPeriode(string $tipe, int $bulan, int $tahun): array
    {
        try {
            $rows = DB::table('potongan_keu')
                ->where('tipe', $tipe)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->get();

            $result = [];
            foreach ($rows as $row) {
                $arr = (array) $row;
                $nik = trim((string)($arr['nik'] ?? ''));
                if ($nik === '') {
                    continue;
                }
                $arr['total_potongan'] = $this->hitungTotalPotongan($arr);
                $result[$nik] = $arr;
            }

            return $result;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("CekNikController getPotonganPeriode failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Halaman Komparasi NIK & Selisih Potongan (Diff Otomatis Periode A vs B).
     */
    public function komparasi(Request $request)
    {
        $tipe = $request->input('tipe', 'gaji');
        if (!array_key_exists($tipe, $this->tipeLabels)) {
            $tipe = 'gaji';
        }

        // Periode B (Pembanding / Berjalan) — default bulan & tahun sekarang
        $bulanB = (int) $request->input('bulan_b', now()->month);
        $tahunB = (int) $request->input('tahun_b', now()->year);

        // Periode A (Acuan / Lalu) — default bulan & tahun sebelumnya
        $defaultA = now()->subMonth();
        $bulanA = (int) $request->input('bulan_a', $defaultA->month);
        $tahunA = (int) $request->input('tahun_a', $defaultA->year);

        $filter = $request->input('filter', 'semua'); // semua, baru, keluar, berubah, tetap
        $search = trim((string) $request->input('search', ''));
        $perPage = (int) $request->input('per_page', 50);
        if ($perPage <= 0) $perPage = 50;

        // Ambil data dari database
        $dataA = $this->getPotonganPeriode($tipe, $bulanA, $tahunA);
        $dataB = $this->getPotonganPeriode($tipe, $bulanB, $tahunB);
        $pegawaiMap = $this->getPegawaiMap();

        $allNiks = array_unique(array_merge(array_keys($dataA), array_keys($dataB)));

        $items = [];
        $stats = [
            'total_pegawai_a'  => count($dataA),
            'total_nominal_a'  => 0,
            'total_pegawai_b'  => count($dataB),
            'total_nominal_b'  => 0,
            'count_baru'       => 0,
            'nominal_baru'     => 0,
            'count_keluar'     => 0,
            'nominal_keluar'   => 0,
            'count_berubah'    => 0,
            'count_naik'       => 0,
            'count_turun'      => 0,
            'count_tetap'      => 0,
            'selisih_netto'    => 0,
        ];

        // Hitung total nominal A & B dari dataset lengkap
        foreach ($dataA as $rA) {
            $stats['total_nominal_a'] += (int) $rA['total_potongan'];
        }
        foreach ($dataB as $rB) {
            $stats['total_nominal_b'] += (int) $rB['total_potongan'];
        }
        $stats['selisih_netto'] = $stats['total_nominal_b'] - $stats['total_nominal_a'];

        foreach ($allNiks as $nik) {
            $inA = isset($dataA[$nik]);
            $inB = isset($dataB[$nik]);
            $rowA = $inA ? $dataA[$nik] : null;
            $rowB = $inB ? $dataB[$nik] : null;

            $totalA = $rowA ? (int) $rowA['total_potongan'] : 0;
            $totalB = $rowB ? (int) $rowB['total_potongan'] : 0;
            $selisih = $totalB - $totalA;

            $peg = $pegawaiMap[$nik] ?? null;
            $nama = $peg['nama'] ?? ($rowB['nama_pegawai'] ?? ($rowA['nama_pegawai'] ?? 'Pegawai'));
            $namaLengkap = $peg['nama_lengkap'] ?? $nama;
            $jabatan = $peg['jabatan'] ?? '-';
            $unitKerja = $peg['unit_kerja'] ?? '-';
            $statusPeg = $peg['status_peg'] ?? 'PT';

            // Periksa rincian perubahan per komponen potongan
            $perubahan = [];
            foreach ($this->kolom as $col) {
                $valA = $rowA ? (int) ($rowA[$col] ?? 0) : 0;
                $valB = $rowB ? (int) ($rowB[$col] ?? 0) : 0;
                if ($valA !== $valB) {
                    $perubahan[] = [
                        'col'     => $col,
                        'label'   => $this->kolomLabels[$col] ?? $col,
                        'val_a'   => $valA,
                        'val_b'   => $valB,
                        'selisih' => $valB - $valA,
                    ];
                }
            }

            // Tentukan status diff
            if (!$inA && $inB) {
                $diffStatus = 'baru';
                $diffType   = 'baru';
                $diffBadge  = 'Pegawai Baru';
                $diffClass  = 'badge-success';
                $stats['count_baru']++;
                $stats['nominal_baru'] += $totalB;
            } elseif ($inA && !$inB) {
                $diffStatus = 'keluar';
                $diffType   = 'keluar';
                $diffBadge  = 'Keluar / Hilang';
                $diffClass  = 'badge-danger';
                $stats['count_keluar']++;
                $stats['nominal_keluar'] += $totalA;
            } elseif ($selisih !== 0) {
                $diffStatus = 'berubah';
                $diffType   = $selisih > 0 ? 'naik' : 'turun';
                $diffBadge  = $selisih > 0 ? 'Nominal Naik' : 'Nominal Turun';
                $diffClass  = $selisih > 0 ? 'badge-warning' : 'badge-info';
                $stats['count_berubah']++;
                if ($selisih > 0) {
                    $stats['count_naik']++;
                } else {
                    $stats['count_turun']++;
                }
            } else {
                $diffStatus = 'tetap';
                $diffType   = 'tetap';
                $diffBadge  = 'Tetap';
                $diffClass  = 'badge-secondary';
                $stats['count_tetap']++;
            }

            $items[] = [
                'nik'          => $nik,
                'nama'         => $nama,
                'nama_lengkap' => $namaLengkap,
                'jabatan'      => $jabatan,
                'unit_kerja'   => $unitKerja,
                'status_peg'   => $statusPeg,
                'total_a'      => $totalA,
                'total_b'      => $totalB,
                'selisih'      => $selisih,
                'diff_status'  => $diffStatus,
                'diff_type'    => $diffType,
                'diff_badge'   => $diffBadge,
                'diff_class'   => $diffClass,
                'row_a'        => $rowA,
                'row_b'        => $rowB,
                'perubahan'    => $perubahan,
                'status_a'     => $rowA['status'] ?? '-',
                'status_b'     => $rowB['status'] ?? '-',
            ];
        }

        // Urutkan item: Pegawai Baru & Berubah & Keluar di atas, lalu Tetap
        usort($items, function ($x, $y) {
            $prioritas = ['baru' => 1, 'keluar' => 2, 'berubah' => 3, 'tetap' => 4];
            $pX = $prioritas[$x['diff_status']] ?? 9;
            $pY = $prioritas[$y['diff_status']] ?? 9;
            if ($pX !== $pY) {
                return $pX <=> $pY;
            }
            return strcmp($x['nama'], $y['nama']);
        });

        // Filter tab (baru, keluar, berubah, tetap)
        $filteredItems = collect($items);
        if ($filter !== 'semua') {
            $filteredItems = $filteredItems->where('diff_status', $filter);
        }

        // Filter search (NIK, Nama, Unit Kerja)
        if ($search !== '') {
            $s = mb_strtolower($search);
            $filteredItems = $filteredItems->filter(function ($item) use ($s) {
                return str_contains(mb_strtolower($item['nik']), $s) ||
                       str_contains(mb_strtolower($item['nama_lengkap']), $s) ||
                       str_contains(mb_strtolower($item['unit_kerja']), $s) ||
                       str_contains(mb_strtolower($item['jabatan']), $s);
            });
        }

        // Export CSV jika diminta
        if ($request->input('export') === 'csv') {
            return $this->exportCsvKomparasi($filteredItems->values()->all(), $tipe, $bulanA, $tahunA, $bulanB, $tahunB);
        }

        // Pagination
        $page = (int) $request->input('page', 1);
        $totalItems = $filteredItems->count();
        $pagedItems = $filteredItems->forPage($page, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $pagedItems,
            $totalItems,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $tipeLabels = $this->tipeLabels;
        $kolomLabels = $this->kolomLabels;

        return view('cek-nik.komparasi', compact(
            'paginator',
            'stats',
            'tipe',
            'bulanA',
            'tahunA',
            'bulanB',
            'tahunB',
            'filter',
            'search',
            'perPage',
            'tipeLabels',
            'kolomLabels'
        ));
    }

    /**
     * Ekspor data komparasi ke file CSV yang dapat langsung dibuka di Excel.
     */
    protected function exportCsvKomparasi(array $items, string $tipe, int $blnA, int $thnA, int $blnB, int $thnB)
    {
        $filename = "Komparasi_{$tipe}_{$blnA}_{$thnA}_vs_{$blnB}_{$thnB}.csv";
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($items, $blnA, $thnA, $blnB, $thnB) {
            $handle = fopen('php://output', 'w');
            // Add UTF-8 BOM so Excel opens it with proper encoding
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'No', 'NIK', 'Nama Pegawai', 'Jabatan', 'Unit Kerja',
                "Potongan ({$blnA}/{$thnA})", "Potongan ({$blnB}/{$thnB})",
                'Selisih (Rp)', 'Status Diff', 'Rincian Perubahan'
            ]);

            $no = 1;
            foreach ($items as $item) {
                $detailPerubahan = [];
                foreach ($item['perubahan'] as $p) {
                    $detailPerubahan[] = "{$p['label']}: Rp " . number_format($p['val_a'], 0, ',', '.') . " -> Rp " . number_format($p['val_b'], 0, ',', '.') . " (" . ($p['selisih'] >= 0 ? '+' : '') . number_format($p['selisih'], 0, ',', '.') . ")";
                }

                fputcsv($handle, [
                    $no++,
                    $item['nik'],
                    $item['nama_lengkap'],
                    $item['jabatan'],
                    $item['unit_kerja'],
                    $item['total_a'],
                    $item['total_b'],
                    $item['selisih'],
                    $item['diff_badge'],
                    implode('; ', $detailPerubahan),
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Cek NIK Bulan Lalu / Acuan.
     */
    public function bulanLalu(Request $request)
    {
        $tipe = $request->input('tipe', 'gaji');
        if (!array_key_exists($tipe, $this->tipeLabels)) {
            $tipe = 'gaji';
        }

        $defaultTarget = now()->subMonth();
        $bulan = (int) $request->input('bulan', $defaultTarget->month);
        $tahun = (int) $request->input('tahun', $defaultTarget->year);
        $search = trim((string) $request->input('search', ''));

        $potonganList = $this->getPotonganPeriode($tipe, $bulan, $tahun);
        $pegawaiMap = $this->getPegawaiMap();

        $rows = [];
        $totalNominal = 0;
        $countTerbit = 0;
        $countDraft = 0;

        foreach ($potonganList as $nik => $pot) {
            $peg = $pegawaiMap[$nik] ?? null;
            $nama = $peg['nama'] ?? ($pot['nama_pegawai'] ?? 'Pegawai');
            $namaLengkap = $peg['nama_lengkap'] ?? $nama;
            $jabatan = $peg['jabatan'] ?? '-';
            $unitKerja = $peg['unit_kerja'] ?? '-';
            $status = $pot['status'] ?? 'N';
            $totalPotongan = (int) $pot['total_potongan'];

            $totalNominal += $totalPotongan;
            if ($status === 'Y') {
                $countTerbit++;
            } else {
                $countDraft++;
            }

            $rows[] = [
                'nik'            => $nik,
                'nama'           => $nama,
                'nama_lengkap'   => $namaLengkap,
                'jabatan'        => $jabatan,
                'unit_kerja'     => $unitKerja,
                'status'         => $status,
                'total_potongan' => $totalPotongan,
                'detail'         => $pot,
            ];
        }

        // Urutkan alfabetis nama
        usort($rows, fn ($a, $b) => strcmp($a['nama_lengkap'], $b['nama_lengkap']));

        // Search filter
        if ($search !== '') {
            $s = mb_strtolower($search);
            $rows = array_values(array_filter($rows, function ($r) use ($s) {
                return str_contains(mb_strtolower($r['nik']), $s) ||
                       str_contains(mb_strtolower($r['nama_lengkap']), $s) ||
                       str_contains(mb_strtolower($r['unit_kerja']), $s);
            }));
        }

        // Pagination
        $page = (int) $request->input('page', 1);
        $perPage = 50;
        $total = count($rows);
        $paged = array_slice($rows, ($page - 1) * $perPage, $perPage);

        $paginator = new LengthAwarePaginator(
            $paged,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $tipeLabels = $this->tipeLabels;
        $kolomLabels = $this->kolomLabels;

        return view('cek-nik.bulan-lalu', compact(
            'paginator',
            'tipe',
            'bulan',
            'tahun',
            'search',
            'totalNominal',
            'countTerbit',
            'countDraft',
            'total',
            'tipeLabels',
            'kolomLabels'
        ));
    }

    /**
     * Cek NIK Bulan Ini / Berjalan.
     */
    public function bulanIni(Request $request)
    {
        $tipe = $request->input('tipe', 'gaji');
        if (!array_key_exists($tipe, $this->tipeLabels)) {
            $tipe = 'gaji';
        }

        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);
        $search = trim((string) $request->input('search', ''));

        $potonganList = $this->getPotonganPeriode($tipe, $bulan, $tahun);
        $pegawaiMap = $this->getPegawaiMap();

        $rows = [];
        $totalNominal = 0;
        $countTerbit = 0;
        $countDraft = 0;

        foreach ($potonganList as $nik => $pot) {
            $peg = $pegawaiMap[$nik] ?? null;
            $nama = $peg['nama'] ?? ($pot['nama_pegawai'] ?? 'Pegawai');
            $namaLengkap = $peg['nama_lengkap'] ?? $nama;
            $jabatan = $peg['jabatan'] ?? '-';
            $unitKerja = $peg['unit_kerja'] ?? '-';
            $status = $pot['status'] ?? 'N';
            $totalPotongan = (int) $pot['total_potongan'];

            $totalNominal += $totalPotongan;
            if ($status === 'Y') {
                $countTerbit++;
            } else {
                $countDraft++;
            }

            $rows[] = [
                'nik'            => $nik,
                'nama'           => $nama,
                'nama_lengkap'   => $namaLengkap,
                'jabatan'        => $jabatan,
                'unit_kerja'     => $unitKerja,
                'status'         => $status,
                'total_potongan' => $totalPotongan,
                'detail'         => $pot,
            ];
        }

        // Urutkan alfabetis nama
        usort($rows, fn ($a, $b) => strcmp($a['nama_lengkap'], $b['nama_lengkap']));

        // Search filter
        if ($search !== '') {
            $s = mb_strtolower($search);
            $rows = array_values(array_filter($rows, function ($r) use ($s) {
                return str_contains(mb_strtolower($r['nik']), $s) ||
                       str_contains(mb_strtolower($r['nama_lengkap']), $s) ||
                       str_contains(mb_strtolower($r['unit_kerja']), $s);
            }));
        }

        // Pagination
        $page = (int) $request->input('page', 1);
        $perPage = 50;
        $total = count($rows);
        $paged = array_slice($rows, ($page - 1) * $perPage, $perPage);

        $paginator = new LengthAwarePaginator(
            $paged,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $tipeLabels = $this->tipeLabels;
        $kolomLabels = $this->kolomLabels;

        return view('cek-nik.bulan-ini', compact(
            'paginator',
            'tipe',
            'bulan',
            'tahun',
            'search',
            'totalNominal',
            'countTerbit',
            'countDraft',
            'total',
            'tipeLabels',
            'kolomLabels'
        ));
    }

    /**
     * Form Hapus Kesalahan NIK Potongan.
     */
    public function hapusForm(Request $request)
    {
        $nik = trim((string) $request->input('nik', ''));
        $tipe = $request->input('tipe', 'gaji');
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        $previewData = null;
        if ($nik !== '') {
            $previewData = DB::table('potongan_keu')
                ->where('tipe', $tipe)
                ->where('nik', $nik)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->first();
        }

        $pegawaiMap = $this->getPegawaiMap();
        $pegawai = $nik !== '' ? ($pegawaiMap[$nik] ?? null) : null;

        return view('cek-nik.hapus', compact(
            'nik', 'tipe', 'bulan', 'tahun', 'previewData', 'pegawai'
        ));
    }

    /**
     * Proses Hapus Kesalahan NIK Potongan.
     */
    public function hapusProses(Request $request)
    {
        $validated = $request->validate([
            'nik'   => 'required|string',
            'tipe'  => 'nullable|string|in:gaji,thr,gaji13',
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2000|max:2100',
        ]);

        $nik   = trim($validated['nik']);
        $tipe  = $validated['tipe'] ?? 'gaji';
        $bulan = (int) $validated['bulan'];
        $tahun = (int) $validated['tahun'];

        $deleted = 0;
        try {
            $deleted = DB::table('potongan_keu')
                ->where('tipe', $tipe)
                ->where('nik', $nik)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->delete();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Hapus potongan NIK {$nik} failed: " . $e->getMessage());
        }

        if ($deleted > 0) {
            return redirect()->route('cek-nik.bulan-ini', ['tipe' => $tipe, 'bulan' => $bulan, 'tahun' => $tahun])
                ->with('success', "Berhasil menghapus {$deleted} data potongan NIK {$nik} untuk periode {$bulan}/{$tahun}.");
        }

        return back()->withInput()->withErrors(['nik' => "Data potongan NIK {$nik} untuk tipe {$tipe} periode {$bulan}/{$tahun} tidak ditemukan."]);
    }
}
