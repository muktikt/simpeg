<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use ZipArchive;
use SimpleXMLElement;

class PotonganKeuController extends Controller
{
    /**
     * Modul Potongan Keuangan (Gaji, THR, Gaji 13).
     * Terhubung langsung dengan tabel potongan_keu di database Supabase PostgreSQL.
     *
     * Disamakan dengan sistem lama (set_potongan_keu.php / tambah_potongan_keu.php):
     *   tbl_potongan_keu (id_potongan_keu, tgl_potongan, nik,
     *     pot_koperasi, pot_darmawanita, pot_air, pot_kas, pot_bjb, pot_bjbs,
     *     pot_asuransi, pot_btn, pot_zakat_profesi, pot_bpjs, pot_bpr,
     *     petugas_entri, disetujui_oleh, tgl_update, status)
     *
     * Tipe: 'gaji', 'thr', 'gaji13' — masing-masing disimpan di session key berbeda.
     * Status berjenjang:
     *   - 'N' atau 'draft': Menunggu Persetujuan Kepegawaian (SDM)
     *   - 'kepegawaian': Disetujui Kepegawaian (SDM) / Siap Diterbitkan Keuangan
     *   - 'Y' atau 'terbit': Diterbitkan & Disetujui Final
     */

    protected array $tipeLabels = [
        'gaji'   => 'Potongan Gaji',
        'thr'    => 'Potongan THR',
        'gaji13' => 'Potongan Gaji 13',
    ];

    protected array $kolom = [
        'pot_koperasi', 'pot_darmawanita', 'pot_air', 'pot_kas',
        'pot_bjb', 'pot_bjbs', 'pot_asuransi', 'pot_btn',
        'pot_zakat_profesi', 'pot_bpjs', 'pot_bpr',
    ];

    protected array $kolomLabels = [
        'pot_koperasi'      => 'Koperasi',
        'pot_darmawanita'   => 'D. Wanita',
        'pot_air'           => 'Ledeng',
        'pot_kas'           => 'KAS',
        'pot_bjb'           => 'BJB',
        'pot_bjbs'          => 'BJB Syariah',
        'pot_asuransi'      => 'Asuransi',
        'pot_btn'           => 'BTN',
        'pot_zakat_profesi' => 'Zakat',
        'pot_bpjs'          => 'BPJS',
        'pot_bpr'           => 'BRI / BPR',
    ];

    protected function all(string $tipe, mixed $bulan = null, ?int $tahun = null): array
    {
        try {
            $query = \Illuminate\Support\Facades\DB::table('potongan_keu')
                ->where('tipe', $tipe);

            if ($bulan !== null && $bulan !== 'all' && (int)$bulan > 0) {
                $query->where('bulan', (int) $bulan);
            }
            if ($tahun !== null && (int)$tahun > 0) {
                $query->where('tahun', (int) $tahun);
            }

            $rows = $query->orderBy('id', 'desc')->get();

            return $rows->map(fn ($r) => (array) $r)->all();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('DB potongan_keu read failed: ' . $e->getMessage());
        }

        return [];
    }

    protected ?array $cachedPegawaiList = null;

    protected function pegawaiList(): array
    {
        if ($this->cachedPegawaiList !== null) {
            return $this->cachedPegawaiList;
        }

        $this->cachedPegawaiList = collect(app(PegawaiController::class)->all())
            ->where('status_peg', '!=', 'PN')
            ->values()
            ->all();

        return $this->cachedPegawaiList;
    }

    protected function pegawaiById(mixed $id): ?array
    {
        if (! $id) {
            return null;
        }

        return collect($this->pegawaiList())->first(function ($p) use ($id) {
            return (string) ($p['id'] ?? '') === (string) $id
                || (string) ($p['db_id'] ?? '') === (string) $id
                || (string) ($p['nik'] ?? '') === (string) $id;
        });
    }

    protected function withCalculated(array $row, ?\Illuminate\Support\Collection $pegawaiMap = null): array
    {
        $nik = (string) ($row['nik'] ?? '');
        $pegId = (string) ($row['pegawai_id'] ?? '');

        $p = null;
        if ($pegawaiMap) {
            $p = $pegawaiMap->get($nik)
                ?: ($pegId ? $pegawaiMap->firstWhere('db_id', $pegId) : null)
                ?: $pegawaiMap->get('TEMP' . $nik);
        } else {
            $p = collect($this->pegawaiList())->first(function ($item) use ($nik, $pegId) {
                return (string) ($item['nik'] ?? '') === $nik
                    || ($pegId !== '' && (string) ($item['db_id'] ?? '') === $pegId)
                    || (string) ($item['nik'] ?? '') === 'TEMP' . $nik;
            });
        }

        $row['nama'] = $p['nama'] ?? '(tidak ditemukan)';
        $row['jabatan'] = $p['jabatan'] ?? '-';
        $row['unit_kerja'] = $p['unit_kerja'] ?? '-';

        $total = 0;
        foreach ($this->kolom as $k) {
            $total += (int) ($row[$k] ?? 0);
        }
        $row['total'] = $total;
        return $row;
    }

    protected function validateTipe(string $tipe): void
    {
        abort_if(! array_key_exists($tipe, $this->tipeLabels), 404, 'Tipe potongan tidak valid.');
    }

    // ───── CRUD ─────

    public function index(Request $request, string $tipe)
    {
        $this->validateTipe($tipe);
        $pegawaiList = $this->pegawaiList();
        $pegawaiMap = collect($pegawaiList)->keyBy('nik');

        $keyword = trim((string) $request->query('q', ''));
        $bulanParam = $request->query('bulan');
        $tahunParam = $request->query('tahun');
        $sort = strtolower(trim((string) $request->query('sort', 'terlama')));

        $bulan = $bulanParam !== null ? ($bulanParam === 'all' ? 'all' : (int) $bulanParam) : (int) now()->month;
        $tahun = $tahunParam !== null ? (int) $tahunParam : (int) now()->year;

        $allItems = collect($this->all($tipe, $bulan, $tahun))
            ->map(fn ($row) => $this->withCalculated($row, $pegawaiMap));

        // Pengurutan (Sorting)
        if ($sort === 'terbaru') {
            $allItems = $allItems->sortByDesc(function ($r) {
                return ($r['tgl_potongan'] ?? '') . '_' . str_pad((string)($r['id'] ?? 0), 10, '0', STR_PAD_LEFT);
            })->values();
        } elseif ($sort === 'nik_asc') {
            $allItems = $allItems->sortBy('nik')->values();
        } elseif ($sort === 'nik_desc') {
            $allItems = $allItems->sortByDesc('nik')->values();
        } elseif ($sort === 'nama_asc') {
            $allItems = $allItems->sortBy('nama', SORT_NATURAL | SORT_FLAG_CASE)->values();
        } elseif ($sort === 'nama_desc') {
            $allItems = $allItems->sortByDesc('nama', SORT_NATURAL | SORT_FLAG_CASE)->values();
        } else {
            // Default: 'terlama' (Urutan sesuai baris import file / ID terkecil ke terbesar)
            $allItems = $allItems->sortBy(function ($r) {
                return ($r['tgl_potongan'] ?? '') . '_' . str_pad((string)($r['id'] ?? 0), 10, '0', STR_PAD_LEFT);
            })->values();
        }

        $filtered = $allItems;
        if ($keyword !== '') {
            $lowerKw = strtolower($keyword);
            $filtered = $filtered->filter(function ($r) use ($lowerKw) {
                return str_contains(strtolower($r['nik'] ?? ''), $lowerKw)
                    || str_contains(strtolower($r['nama'] ?? ''), $lowerKw)
                    || str_contains(strtolower($r['jabatan'] ?? ''), $lowerKw)
                    || str_contains(strtolower($r['unit_kerja'] ?? ''), $lowerKw);
            })->values();
        }

        // Pagination 20 Data per Halaman
        $perPage = 20;
        $currentPage = (int) $request->query('page', 1);
        if ($currentPage < 1) $currentPage = 1;

        $totalFiltered = $filtered->count();
        $pagedItems = $filtered->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $pagedItems,
            $totalFiltered,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $totalPegawai = count($pegawaiList);
        $sudahMasuk   = $allItems->pluck('nik')->unique()->count();
        $belumMasuk   = max(0, $totalPegawai - $sudahMasuk);

        return view('potongan-keu.index', [
            'paginator'   => $paginator,
            'items'       => $pagedItems,
            'keyword'     => $keyword,
            'sort'        => $sort,
            'bulan'       => $bulan,
            'tahun'       => $tahun,
            'totalItems'  => $allItems->count(),
            'filteredCount' => $totalFiltered,
            'tipe'        => $tipe,
            'tipeLabel'   => $this->tipeLabels[$tipe],
            'kolom'       => $this->kolom,
            'kolomLabels' => $this->kolomLabels,
            'belumMasuk'  => $belumMasuk,
        ]);
    }

    public function create(string $tipe)
    {
        $this->validateTipe($tipe);
        return view('potongan-keu.create', [
            'tipe'        => $tipe,
            'tipeLabel'   => $this->tipeLabels[$tipe],
            'kolom'       => $this->kolom,
            'kolomLabels' => $this->kolomLabels,
            'pegawaiList' => $this->pegawaiList(),
        ]);
    }

    public function store(Request $request, string $tipe)
    {
        $this->validateTipe($tipe);
        $validated = $this->validateData($request);

        // Cek duplikat NIK + bulan + tahun
        $peg = $this->pegawaiById((int) $validated['pegawai_id']);
        abort_if(! $peg, 404, 'Pegawai tidak ditemukan.');

        $bulan = now()->month;
        $tahun = now()->year;
        $existing = collect($this->all($tipe))->first(function ($r) use ($peg, $bulan, $tahun) {
            $d = \Carbon\Carbon::parse($r['tgl_potongan']);
            return $r['nik'] === $peg['nik'] && $d->month === $bulan && $d->year === $tahun;
        });

        if ($existing) {
            return back()->withErrors(['pegawai_id' => 'Pegawai ini sudah ada potongan untuk bulan ini.'])->withInput();
        }

        $dbPegawaiId = $peg['db_id'] ?? null;
        if (! $dbPegawaiId && ! empty($peg['nik'])) {
            $dbPegawaiId = \Illuminate\Support\Facades\DB::table('pegawai')->where('nik', $peg['nik'])->value('id');
        }

        $row = [
    'tipe'          => $tipe,
    'tgl_potongan'  => now()->toDateString(),
    'nik'           => $peg['nik'],
    'pegawai_id'    => $dbPegawaiId,
    'bulan'         => $bulan,
    'tahun'         => $tahun,
    'petugas_entri' => session('simpeg_user.nama_peg', 'Admin'),
    'status'        => 'N',
    'created_at'    => now(),
    'updated_at'    => now(),
];
        foreach ($this->kolom as $k) {
            $row[$k] = (int) ($validated[$k] ?? 0);
        }

        \Illuminate\Support\Facades\DB::table('potongan_keu')->insert($row);

        return redirect()->route('potongan-keu.index', $tipe)->with('success', 'Potongan berhasil ditambahkan dan tersimpan di database.');
    }

    public function edit(string $tipe, int $id)
    {
        $this->validateTipe($tipe);
        $item = collect($this->all($tipe))->firstWhere('id', $id);
        abort_if(! $item, 404);

        return view('potongan-keu.edit', [
            'item'        => $item,
            'tipe'        => $tipe,
            'tipeLabel'   => $this->tipeLabels[$tipe],
            'kolom'       => $this->kolom,
            'kolomLabels' => $this->kolomLabels,
            'pegawaiList' => $this->pegawaiList(),
        ]);
    }

    public function update(Request $request, string $tipe, int $id)
    {
        $this->validateTipe($tipe);
        $validated = $this->validateData($request);
        $peg = $this->pegawaiById((int) $validated['pegawai_id']);
        abort_if(! $peg, 404);

        $updateData = [
        'petugas_entri' => session('simpeg_user.nama_peg', 'Admin'),
        'updated_at' => now(),
    ];
        foreach ($this->kolom as $k) {
            $updateData[$k] = (int) ($validated[$k] ?? 0);
        }

        \Illuminate\Support\Facades\DB::table('potongan_keu')->where('id', $id)->where('tipe', $tipe)->update($updateData);

        return redirect()->route('potongan-keu.index', $tipe)->with('success', 'Potongan berhasil diperbarui di database.');
    }

    public function destroy(string $tipe, int $id)
    {
        $this->validateTipe($tipe);
        \Illuminate\Support\Facades\DB::table('potongan_keu')->where('id', $id)->where('tipe', $tipe)->delete();

        return redirect()->route('potongan-keu.index', $tipe)->with('success', 'Potongan berhasil dihapus dari database.');
    }

    // ───── FITUR IMPORT EXCEL / CSV MASSAL ─────

    /**
     * Unduh template file spreadsheet (.csv) siap pakai untuk import potongan.
     */
    public function downloadTemplate(Request $request, string $tipe)
    {
        $this->validateTipe($tipe);
        $isSample = $request->boolean('sample') || $request->query('format') === 'sample';
        $pegawaiList = $this->pegawaiList();

        if ($isSample) {
            $pegawaiList = array_slice($pegawaiList, 0, 1);
            $filename = "Template_Contoh_1_Pegawai_" . strtoupper($tipe) . "_" . date('Ymd') . ".csv";
        } else {
            $filename = "Template_Set_Potongan_" . strtoupper($tipe) . "_" . date('Ymd') . ".csv";
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = ['NO', 'NIK', 'NAMA', 'KOPERASI', 'D. WANITA', 'LEDENG', 'KAS', 'BJB', 'BJB SYARIAH', 'ASURANSI', 'BTN', 'ZAKAT', 'BPJS', 'BRI', 'JML POT.'];

        $callback = function () use ($columns, $pegawaiList) {
            $handle = fopen('php://output', 'w');
            // Add UTF-8 BOM for Excel compatibility
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $columns);

            $no = 1;
            foreach ($pegawaiList as $p) {
                $row = [
                    $no++,
                    $p['nik'] ?? '',
                    $p['nama'] ?? '',
                    0, // KOPERASI
                    0, // D. WANITA
                    0, // LEDENG
                    0, // KAS
                    0, // BJB
                    0, // BJB SYARIAH
                    0, // ASURANSI
                    0, // BTN
                    0, // ZAKAT
                    0, // BPJS
                    0, // BRI
                    0, // JML POT.
                ];
                fputcsv($handle, $row);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import massal data potongan dari file Excel (.xlsx / .xls / .csv).
     */
    public function importExcel(Request $request, string $tipe)
    {
        $this->validateTipe($tipe);

        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '512M');
        @set_time_limit(0);

        $request->validate([
            'file_excel' => 'required|file|max:10240',
            'mode'       => 'nullable|in:update,skip',
        ]);

        $file = $request->file('file_excel');
        $extension = strtolower($file->getClientOriginalExtension());
        $mode = $request->input('mode', 'update');

        $rows = [];

        if (in_array($extension, ['csv', 'txt'], true)) {
            $rows = $this->parseCsv($file->getRealPath());
        } elseif ($extension === 'xlsx') {
            if (! class_exists(\ZipArchive::class)) {
                return back()->with('error', 'Ekstensi PHP "zip" baru saja diaktifkan di php.ini. Silakan restart terminal "php artisan serve" Anda, atau simpan/ekspor file Excel ke format CSV (.csv) lalu upload kembali.');
            }
            $rows = $this->parseXlsx($file->getRealPath());
        } else {
            // Coba parse sebagai CSV jika extension lain
            $rows = $this->parseCsv($file->getRealPath());
        }

        if (empty($rows)) {
            return back()->with('error', 'Gagal membaca isi file atau file kosong. Pastikan format sesuai template.');
        }

        // Cari baris header yang mengandung kolom 'NIK' (bila ada baris kosong di awal file)
        $header = null;
        while (! empty($rows)) {
            $candidate = array_shift($rows);
            $hasNik = false;
            foreach ($candidate as $colVal) {
                if (str_contains(strtolower(trim((string) $colVal)), 'nik')) {
                    $hasNik = true;
                    break;
                }
            }
            if ($hasNik) {
                $header = $candidate;
                break;
            }
        }

        if (! $header) {
            return back()->with('error', 'Kolom header "NIK" tidak ditemukan di dalam file. Pastikan format sesuai.');
        }

        // Mapping alias kolom fleksibel
        $headerMap = [];
        foreach ($header as $colIdx => $colName) {
            $cleanName = strtolower(trim((string) $colName));
            if ($cleanName === '') {
                continue;
            }

            if (str_contains($cleanName, 'nik')) {
                $headerMap['nik'] = $colIdx;
            } elseif (str_contains($cleanName, 'koperasi')) {
                $headerMap['pot_koperasi'] = $colIdx;
            } elseif (str_contains($cleanName, 'wanita') || str_contains($cleanName, 'darma') || str_contains($cleanName, 'dharma')) {
                $headerMap['pot_darmawanita'] = $colIdx;
            } elseif (str_contains($cleanName, 'ledeng') || str_contains($cleanName, 'air') || str_contains($cleanName, 'pdam')) {
                $headerMap['pot_air'] = $colIdx;
            } elseif ($cleanName === 'kas' || str_contains($cleanName, 'pot_kas')) {
                $headerMap['pot_kas'] = $colIdx;
            } elseif (str_contains($cleanName, 'bjb syariah') || str_contains($cleanName, 'bjbs') || str_contains($cleanName, 'syariah')) {
                $headerMap['pot_bjbs'] = $colIdx;
            } elseif (str_contains($cleanName, 'bjb') && ! str_contains($cleanName, 'syariah')) {
                $headerMap['pot_bjb'] = $colIdx;
            } elseif (str_contains($cleanName, 'asuransi') || str_contains($cleanName, 'bumida')) {
                $headerMap['pot_asuransi'] = $colIdx;
            } elseif (str_contains($cleanName, 'btn')) {
                $headerMap['pot_btn'] = $colIdx;
            } elseif (str_contains($cleanName, 'zakat') || str_contains($cleanName, 'infaq')) {
                $headerMap['pot_zakat_profesi'] = $colIdx;
            } elseif (str_contains($cleanName, 'bpjs')) {
                $headerMap['pot_bpjs'] = $colIdx;
            } elseif (str_contains($cleanName, 'bpr') || str_contains($cleanName, 'bri') || str_contains($cleanName, 'bank')) {
                $headerMap['pot_bpr'] = $colIdx;
            }
        }

        // Jika NIK belum terpetakan, fallback ke indeks 1 atau 0
        if (! isset($headerMap['nik'])) {
            $headerMap['nik'] = 1;
        }

        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);
        if ($bulan < 1 || $bulan > 12) $bulan = (int) now()->month;
        if ($tahun < 2000) $tahun = (int) now()->year;

        $petugasEntri = session('simpeg_user.nama_peg', 'Admin (Excel Import)');
        $now = now();
        $today = $now->toDateString();

        // 1. Ambil semua NIK -> Pegawai UUID sekali saja dalam 1 query cepat
        $pegawaiDbIds = \Illuminate\Support\Facades\DB::table('pegawai')->pluck('id', 'nik')->all();

        // 2. Ambil data potongan_keu bulan ini sekali saja dalam 1 query cepat
        $existingRows = \Illuminate\Support\Facades\DB::table('potongan_keu')
            ->where('tipe', $tipe)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get()
            ->keyBy('nik');

        $importedCount = 0;
        $updatedCount = 0;
        $insertBatch = [];
        $updateBatch = [];

        foreach ($rows as $r) {
            $rawNik = trim((string) ($r[$headerMap['nik']] ?? ''));
            // Bersihkan petik/spasi tapi pertahankan huruf dan angka (karena NIK pegawai ada yang diawali TEMP seperti TEMP0027)
            $nik = strtoupper(preg_replace('/[^A-Za-z0-9\-_]/', '', $rawNik));

            // Jika NIK hanya angka 4 digit dan ada padanannya dengan awalan TEMP di master pegawai, sesuaikan otomatis
            if (strlen($nik) === 4 && isset($pegawaiDbIds['TEMP' . $nik])) {
                $nik = 'TEMP' . $nik;
            }

            if (empty($nik)) {
                continue;
            }

            $values = [];
            foreach ($this->kolom as $k) {
                $colIdx = $headerMap[$k] ?? null;
                $valStr = $colIdx !== null ? (string) ($r[$colIdx] ?? '0') : '0';
                $cleanVal = (int) preg_replace('/[^0-9]/', '', $valStr);
                $values[$k] = $cleanVal;
            }

            $dbPegId = $pegawaiDbIds[$nik] ?? ($pegawaiDbIds['TEMP' . $nik] ?? null);

            if ($existingRows->has($nik)) {
                if ($mode === 'update') {
                    $existing = $existingRows->get($nik);
                    $rowUpdate = [
                        'id'            => $existing->id,
                        'tipe'          => $existing->tipe,
                        'tgl_potongan'  => $existing->tgl_potongan,
                        'nik'           => $nik,
                        'pegawai_id'    => $existing->pegawai_id ?: $dbPegId,
                        'bulan'         => $bulan,
                        'tahun'         => $tahun,
                        'petugas_entri' => $petugasEntri,
                        'status'        => $existing->status ?? 'N',
                        'updated_at'    => $now,
                    ];
                    foreach ($values as $k => $v) {
                        $rowUpdate[$k] = $v;
                    }
                    $updateBatch[] = $rowUpdate;
                    $updatedCount++;
                }
            } else {
                $newRow = [
                    'tipe'          => $tipe,
                    'tgl_potongan'  => $today,
                    'nik'           => $nik,
                    'pegawai_id'    => $dbPegId,
                    'bulan'         => $bulan,
                    'tahun'         => $tahun,
                    'petugas_entri' => $petugasEntri,
                    'status'        => 'N',
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
                foreach ($values as $k => $v) {
                    $newRow[$k] = $v;
                }
                $insertBatch[] = $newRow;
                $importedCount++;
            }
        }

        // 3. Simpan data ke database secara transaksional & batch (efisien dan cepat)
        \Illuminate\Support\Facades\DB::transaction(function () use ($insertBatch, $updateBatch) {
            if (! empty($insertBatch)) {
                foreach (array_chunk($insertBatch, 100) as $chunk) {
                    \Illuminate\Support\Facades\DB::table('potongan_keu')->insert($chunk);
                }
            }

            if (! empty($updateBatch)) {
                $updateColumns = array_merge(['petugas_entri', 'updated_at', 'pegawai_id'], $this->kolom);
                foreach (array_chunk($updateBatch, 100) as $chunk) {
                    \Illuminate\Support\Facades\DB::table('potongan_keu')->upsert($chunk, ['id'], $updateColumns);
                }
            }
        });

        return redirect()->route('potongan-keu.index', ['tipe' => $tipe, 'bulan' => $bulan, 'tahun' => $tahun])->with(
            'success',
            "Berhasil mengimpor potongan {$this->tipeLabels[$tipe]} dari file Excel ke database periode {$bulan}/{$tahun}: {$importedCount} data baru ditambahkan, {$updatedCount} data diperbarui."
        );
    }

    /**
     * Salin seluruh potongan dari bulan sebelumnya ke bulan target jika bulan target belum ada data / ingin disamakan.
     */
    public function salinBulanLalu(Request $request, string $tipe)
    {
        $this->validateTipe($tipe);

        $targetBulan = (int) $request->input('target_bulan', now()->month);
        $targetTahun = (int) $request->input('target_tahun', now()->year);

        // Sumber: cari data bulan sebelumnya
        $sumberBulan = $targetBulan === 1 ? 12 : $targetBulan - 1;
        $sumberTahun = $targetBulan === 1 ? $targetTahun - 1 : $targetTahun;

        $sourceRows = \Illuminate\Support\Facades\DB::table('potongan_keu')
            ->where('tipe', $tipe)
            ->where('bulan', $sumberBulan)
            ->where('tahun', $sumberTahun)
            ->get();

        // Jika bulan persis sebelumnya kosong, cari periode terakhir yang ada datanya
        if ($sourceRows->isEmpty()) {
            $lastRecord = \Illuminate\Support\Facades\DB::table('potongan_keu')
                ->where('tipe', $tipe)
                ->orderByDesc('tahun')
                ->orderByDesc('bulan')
                ->first();

            if ($lastRecord) {
                $sumberBulan = (int) $lastRecord->bulan;
                $sumberTahun = (int) $lastRecord->tahun;
                $sourceRows = \Illuminate\Support\Facades\DB::table('potongan_keu')
                    ->where('tipe', $tipe)
                    ->where('bulan', $sumberBulan)
                    ->where('tahun', $sumberTahun)
                    ->get();
            }
        }

        if ($sourceRows->isEmpty()) {
            return back()->with('error', 'Tidak ditemukan riwayat potongan sebelumnya untuk disalin.');
        }

        $existingNiks = \Illuminate\Support\Facades\DB::table('potongan_keu')
            ->where('tipe', $tipe)
            ->where('bulan', $targetBulan)
            ->where('tahun', $targetTahun)
            ->pluck('nik')
            ->all();
        $existingMap = array_flip($existingNiks);

        $now = now();
        $today = $now->toDateString();
        $petugasEntri = session('simpeg_user.nama_peg', 'Admin (Salin Otomatis)');

        $insertBatch = [];
        foreach ($sourceRows as $src) {
            if (isset($existingMap[$src->nik])) {
                continue;
            }

            $newRow = [
                'tipe'          => $tipe,
                'tgl_potongan'  => $today,
                'nik'           => $src->nik,
                'pegawai_id'    => $src->pegawai_id,
                'bulan'         => $targetBulan,
                'tahun'         => $targetTahun,
                'petugas_entri' => $petugasEntri,
                'status'        => 'N',
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
            foreach ($this->kolom as $k) {
                $newRow[$k] = (int) ($src->$k ?? 0);
            }
            $insertBatch[] = $newRow;
        }

        if (!empty($insertBatch)) {
            foreach (array_chunk($insertBatch, 100) as $chunk) {
                \Illuminate\Support\Facades\DB::table('potongan_keu')->insert($chunk);
            }
        }

        $bulanNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $srcName = ($bulanNames[$sumberBulan] ?? $sumberBulan) . ' ' . $sumberTahun;
        $tgtName = ($bulanNames[$targetBulan] ?? $targetBulan) . ' ' . $targetTahun;

        return redirect()->route('potongan-keu.index', ['tipe' => $tipe, 'bulan' => $targetBulan, 'tahun' => $targetTahun])
            ->with('success', "Berhasil menyalin " . count($insertBatch) . " data potongan dari {$srcName} ke periode {$tgtName} (status: Draft).");
    }

    /**
     * Parse CSV / TXT file ke format array baris.
     */
    protected function parseCsv(string $path): array
    {
        $rows = [];
        if (($handle = fopen($path, 'r')) !== false) {
            // Deteksi delimiter (koma atau titik koma)
            $firstLine = fgets($handle);
            rewind($handle);
            $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

            while (($data = fgetcsv($handle, 4096, $delimiter)) !== false) {
                // Filter baris kosong
                if (count(array_filter($data, fn ($v) => trim((string)$v) !== '')) > 0) {
                    $rows[] = $data;
                }
            }
            fclose($handle);
        }
        return $rows;
    }

    /**
     * Parse .xlsx file native menggunakan ZipArchive & SimpleXMLElement.
     */
    protected function parseXlsx(string $path): array
    {
        if (! class_exists(\ZipArchive::class)) {
            return $this->parseCsv($path);
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return $this->parseCsv($path);
        }

        // 1. Ambil sharedStrings
        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml) {
            $xml = simplexml_load_string($sharedXml);
            if ($xml) {
                foreach ($xml->si as $si) {
                    $text = (string) $si->t;
                    if (empty($text) && isset($si->r)) {
                        $parts = [];
                        foreach ($si->r as $r) {
                            $parts[] = (string) $r->t;
                        }
                        $text = implode('', $parts);
                    }
                    $sharedStrings[] = $text;
                }
            }
        }

        // 2. Ambil worksheet pertama
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $rows = [];
        if ($sheetXml) {
            $xml = simplexml_load_string($sheetXml);
            if ($xml && isset($xml->sheetData->row)) {
                foreach ($xml->sheetData->row as $row) {
                    $rowVals = [];
                    foreach ($row->c as $cell) {
                        $cellType = (string) $cell['t'];
                        $val = (string) $cell->v;

                        if ($cellType === 's' && isset($sharedStrings[(int) $val])) {
                            $val = $sharedStrings[(int) $val];
                        }

                        // Petakan kolom berdasarkan koordinat sel (misal: A1, B1, C1) agar kolom kosong tidak menggeser posisi
                        $cellRef = (string) $cell['r'];
                        $colLetters = preg_replace('/[^A-Za-z]/', '', $cellRef);
                        $colIdx = 0;
                        for ($i = 0; $i < strlen($colLetters); $i++) {
                            $colIdx = $colIdx * 26 + (ord(strtoupper($colLetters[$i])) - ord('A') + 1);
                        }
                        $colIdx = max(0, $colIdx - 1);
                        $rowVals[$colIdx] = $val;
                    }

                    // Urutkan key array kolom dari 0
                    if (! empty($rowVals)) {
                        ksort($rowVals);
                        // Normalisasi indeks dari 0 sampai indeks terbesar
                        $maxIdx = max(array_keys($rowVals));
                        $normalizedRow = [];
                        for ($k = 0; $k <= $maxIdx; $k++) {
                            $normalizedRow[$k] = $rowVals[$k] ?? '';
                        }

                        if (count(array_filter($normalizedRow, fn ($v) => trim((string)$v) !== '')) > 0) {
                            $rows[] = $normalizedRow;
                        }
                    }
                }
            }
        }

        $zip->close();
        return $rows;
    }

    // ───── PROSES TERBIT POTONGAN (REALTIME & BERJENJANG) ─────

    public function terbitIndex(Request $request, string $tipe)
    {
        $this->validateTipe($tipe);
        return redirect()->route('approval.index', [
            'jenis' => 'potongan_' . $tipe,
            'bulan' => $request->query('bulan', 'all'),
            'tahun' => $request->query('tahun', 'all'),
        ]);
    }

    /**
     * Endpoint API JSON untuk Polling Realtime Status Terbit Potongan.
     */
    public function realtimeStatus(Request $request, string $tipe)
    {
        $this->validateTipe($tipe);
        $pegawaiList = $this->pegawaiList();
        $pegawaiMap = collect($pegawaiList)->keyBy('nik');

        $bulanParam = $request->query('bulan');
        $tahunParam = $request->query('tahun');
        $bulan = $bulanParam !== null ? ($bulanParam === 'all' ? 'all' : (int) $bulanParam) : (int) now()->month;
        $tahun = $tahunParam !== null ? (int) $tahunParam : (int) now()->year;

        $allItems = collect($this->all($tipe, $bulan, $tahun))
            ->map(fn ($row) => $this->withCalculated($row, $pegawaiMap))
            ->sortBy('nik')
            ->values();

        $countDraft = $allItems->filter(fn ($r) => in_array($r['status'] ?? 'N', ['N', 'draft', null, ''], true))->count();
        $countDisetujuiSdm = $allItems->filter(fn ($r) => ($r['status'] ?? '') === 'kepegawaian')->count();
        $countFinal = $allItems->filter(fn ($r) => in_array($r['status'] ?? '', ['Y', 'terbit'], true))->count();

        $itemsPending = $allItems->where('status', '!=', 'Y')->values();
        $totals = [];
        foreach ($this->kolom as $k) {
            $totals[$k] = $itemsPending->sum($k);
        }
        $totals['grand_total'] = $itemsPending->sum('total');

        return response()->json([
            'success'              => true,
            'timestamp'            => now()->format('H:i:s'),
            'countDraft'           => $countDraft,
            'countDisetujuiSdm'    => $countDisetujuiSdm,
            'countFinal'           => $countFinal,
            'totalPending'         => $countDraft + $countDisetujuiSdm,
            'belumDiterbitkan'     => $countDraft + $countDisetujuiSdm,
            'sudahDiterbitkan'     => $countFinal,
            'grandTotalFormatted'  => 'Rp ' . number_format($totals['grand_total'], 0, ',', '.'),
            'items'                => $allItems->map(function ($it) {
                $status = $it['status'] ?? 'N';
                $isTerbit = in_array($status, ['Y', 'terbit'], true);
                $isSdmApproved = ($status === 'kepegawaian');
                $isDraft = (!$isTerbit && !$isSdmApproved);

                if ($isTerbit) {
                    $statusLabel = 'Diterbitkan Final';
                    $statusClass = 'badge-status-terbit';
                } elseif ($isSdmApproved) {
                    $statusLabel = 'Disetujui SDM';
                    $statusClass = 'badge-status-kepegawaian';
                } else {
                    $statusLabel = 'Draft (Menunggu SDM)';
                    $statusClass = 'badge-status-menunggu';
                }

                return [
                    'id'                        => $it['id'],
                    'nik'                       => $it['nik'],
                    'nama'                      => $it['nama'],
                    'status'                    => $status,
                    'statusLabel'               => $statusLabel,
                    'statusClass'               => $statusClass,
                    'isDraft'                   => $isDraft,
                    'isSdmApproved'             => $isSdmApproved,
                    'isTerbit'                  => $isTerbit,
                    'disetujui_oleh'            => $it['disetujui_oleh'] ?? '-',
                    'disetujui_kepegawaian_oleh' => $it['disetujui_kepegawaian_oleh'] ?? '-',
                    'totalFormatted'            => 'Rp ' . number_format($it['total'], 0, ',', '.'),
                ];
            }),
        ]);
    }

    /**
     * Aksi Persetujuan dari Kepegawaian (SDM).
     * Mendukung persetujuan per-pegawai, per-checkbox (array IDs), atau massal seluruh draft.
     */
    public function setujuiKepegawaian(Request $request, string $tipe)
    {
        $this->validateTipe($tipe);

        $userLevel = (int) session('simpeg_user.userlevel', 0);
        $userRole  = (int) session('simpeg_role', 0);
        $userNik   = (string) session('simpeg_user.nik', '');
        $sdmNik    = (string) config('simpeg_approval.kepegawaian', '1711254');
        $isSdm = ($userLevel === 1 || $userRole === 1 || $userNik === $sdmNik);

        if (! $isSdm) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Hanya Bagian Kepegawaian (SDM) yang berhak menyetujui draf potongan.'], 403);
            }
            return redirect()->back()->with('error', 'Hanya Bagian Kepegawaian (SDM) yang berhak menyetujui draf potongan.');
        }

        $targetIds = $request->input('ids');
        $targetId = $request->input('id');
        $approver = session('simpeg_user.nama_peg', 'SDM / Kepegawaian');

        $query = \Illuminate\Support\Facades\DB::table('potongan_keu')->where('tipe', $tipe);

        if (! empty($targetIds) && is_array($targetIds)) {
            $query->whereIn('id', $targetIds);
        } elseif ($targetId) {
            $query->where('id', $targetId);
        } else {
            $query->where(function ($q) {
                $q->where('status', 'N')->orWhere('status', 'draft')->orWhereNull('status');
            });
            $bulan = $request->input('bulan');
            $tahun = $request->input('tahun');
            if ($bulan && $bulan !== 'all') {
                $query->where('bulan', (int) $bulan);
            }
            if ($tahun) {
                $query->where('tahun', (int) $tahun);
            }
        }

        $affectedCount = $query->update([
            'status'                     => 'kepegawaian',
            'disetujui_kepegawaian_oleh' => $approver,
            'tgl_setuju_kepegawaian'     => now(),
            'updated_at'                 => now(),
        ]);

        // Sinkronisasi ke tabel payroll
        $affectedRowsQuery = \Illuminate\Support\Facades\DB::table('potongan_keu')->where('tipe', $tipe);
        if (! empty($targetIds) && is_array($targetIds)) {
            $affectedRowsQuery->whereIn('id', $targetIds);
        } elseif ($targetId) {
            $affectedRowsQuery->where('id', $targetId);
        }
        $affectedRows = $affectedRowsQuery->get();
        foreach ($affectedRows as $row) {
            $this->syncToPayroll($row);
        }

        $msg = "{$affectedCount} data potongan {$this->tipeLabels[$tipe]} berhasil disetujui oleh Kepegawaian (SDM) dan siap diterbitkan final.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $msg, 'affectedCount' => $affectedCount]);
        }

        return redirect()->route('approval.index', ['jenis' => 'potongan_' . $tipe])->with('success', $msg);
    }

    /**
     * Aksi Penerbitan Final dari Keuangan.
     * Mendukung penerbitan per-pegawai, per-checkbox (array IDs), atau massal yang siap terbit.
     */
    public function terbitkan(Request $request, string $tipe)
    {
        $this->validateTipe($tipe);

        $userLevel = (int) session('simpeg_user.userlevel', 0);
        $userRole  = (int) session('simpeg_role', 0);
        $userNik   = (string) session('simpeg_user.nik', '');
        $keuNik    = (string) config('simpeg_approval.keuangan', '1711296');
        $isKeu = ($userLevel === 2 || $userRole === 2 || $userNik === $keuNik);

        if (! $isKeu) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Hanya Bagian Keuangan yang berhak menerbitkan final potongan.'], 403);
            }
            return redirect()->back()->with('error', 'Hanya Bagian Keuangan yang berhak menerbitkan final potongan.');
        }

        $targetIds = $request->input('ids');
        $targetId = $request->input('id');
        $approverNik = session('simpeg_user.nik', config('simpeg_approval.keuangan', '0'));
        $approverName = session('simpeg_user.nama_peg', 'Keuangan');

        $query = \Illuminate\Support\Facades\DB::table('potongan_keu')->where('tipe', $tipe);

        if (! empty($targetIds) && is_array($targetIds)) {
            $query->whereIn('id', $targetIds);
        } elseif ($targetId) {
            $query->where('id', $targetId);
        } else {
            $query->where('status', '!=', 'Y');
            $bulan = $request->input('bulan');
            $tahun = $request->input('tahun');
            if ($bulan && $bulan !== 'all') {
                $query->where('bulan', (int) $bulan);
            }
            if ($tahun) {
                $query->where('tahun', (int) $tahun);
            }
        }

        $affectedCount = $query->update([
            'status'         => 'Y',
            'disetujui_oleh' => $approverName . ' (' . $approverNik . ')',
            'updated_at'     => now(),
        ]);

        // Sinkronisasi ke tabel payroll
        $affectedRowsQuery = \Illuminate\Support\Facades\DB::table('potongan_keu')->where('tipe', $tipe);
        if (! empty($targetIds) && is_array($targetIds)) {
            $affectedRowsQuery->whereIn('id', $targetIds);
        } elseif ($targetId) {
            $affectedRowsQuery->where('id', $targetId);
        }
        $affectedRows = $affectedRowsQuery->get();
        foreach ($affectedRows as $row) {
            $this->syncToPayroll($row);
        }

        $msg = "{$affectedCount} data potongan {$this->tipeLabels[$tipe]} berhasil diterbitkan secara sah dan final di database.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $msg, 'affectedCount' => $affectedCount]);
        }

        return redirect()->route('approval.index', ['jenis' => 'potongan_' . $tipe])->with('success', $msg);
    }

    /**
     * Aksi Batalkan Penerbitan (Kembalikan status ke Draf / Menunggu SDM).
     * Mendukung per-pegawai, per-checkbox (array IDs), atau massal.
     */
    public function batalTerbit(Request $request, string $tipe)
    {
        $this->validateTipe($tipe);

        $userLevel = (int) session('simpeg_user.userlevel', 0);
        $userRole  = (int) session('simpeg_role', 0);
        $userNik   = (string) session('simpeg_user.nik', '');
        $keuNik    = (string) config('simpeg_approval.keuangan', '1711296');
        $isKeu = ($userLevel === 2 || $userRole === 2 || $userNik === $keuNik);

        if (! $isKeu) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Hanya Bagian Keuangan yang berhak membatalkan penerbitan.'], 403);
            }
            return redirect()->back()->with('error', 'Hanya Bagian Keuangan yang berhak membatalkan penerbitan.');
        }

        $targetIds = $request->input('ids');
        $targetId = $request->input('id');

        $query = \Illuminate\Support\Facades\DB::table('potongan_keu')->where('tipe', $tipe);

        if (! empty($targetIds) && is_array($targetIds)) {
            $query->whereIn('id', $targetIds);
        } elseif ($targetId) {
            $query->where('id', $targetId);
        } else {
            $query->whereIn('status', ['Y', 'terbit', 'kepegawaian']);
            $bulan = $request->input('bulan');
            $tahun = $request->input('tahun');
            if ($bulan && $bulan !== 'all') {
                $query->where('bulan', (int) $bulan);
            }
            if ($tahun) {
                $query->where('tahun', (int) $tahun);
            }
        }

        $affectedCount = $query->update([
            'status'                     => 'N',
            'disetujui_oleh'             => null,
            'disetujui_kepegawaian_oleh' => null,
            'tgl_setuju_kepegawaian'     => null,
            'updated_at'                 => now(),
        ]);

        $msg = "{$affectedCount} data potongan {$this->tipeLabels[$tipe]} berhasil dibatalkan penerbitannya dan dikembalikan ke status Draf.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $msg, 'affectedCount' => $affectedCount]);
        }

        return redirect()->route('approval.index', ['jenis' => 'potongan_' . $tipe])->with('success', $msg);
    }

    /**
     * Sinkronisasi potongan keuangan ke tabel payroll Supabase dan file storage lokal.
     */
    public function syncToPayroll(object|array $p): void
    {
        $p = (array) $p;
        if (($p['tipe'] ?? 'gaji') !== 'gaji') {
            return;
        }

        try {
            $nik = $p['nik'] ?? null;
            $pegawaiId = $p['pegawai_id'] ?? null;
            $bulan = (int) ($p['bulan'] ?? now()->month);
            $tahun = (int) ($p['tahun'] ?? now()->year);

            $query = \Illuminate\Support\Facades\DB::table('payroll')
                ->where('bulan', $bulan)
                ->where('tahun', $tahun);

            if ($pegawaiId) {
                $query->where(function ($q) use ($pegawaiId, $nik) {
                    $q->where('pegawai_id', $pegawaiId);
                    if ($nik) {
                        $q->orWhereIn('pegawai_id', function ($sub) use ($nik) {
                            $sub->select('id')->from('pegawai')->where('nik', $nik);
                        });
                    }
                });
            } elseif ($nik) {
                $query->whereIn('pegawai_id', function ($sub) use ($nik) {
                    $sub->select('id')->from('pegawai')->where('nik', $nik);
                });
            } else {
                return;
            }

            $payroll = $query->first();
            if (! $payroll) {
                return;
            }

            $potKoperasi = (int) ($p['pot_koperasi'] ?? 0);
            $potDarmawanita = (int) ($p['pot_darmawanita'] ?? 0);
            $potAir = (int) ($p['pot_air'] ?? 0);
            $potKas = (int) ($p['pot_kas'] ?? 0);
            $potBjb = (int) ($p['pot_bjb'] ?? 0);
            $potBjbs = (int) ($p['pot_bjbs'] ?? 0);
            $potAsuransi = (int) ($p['pot_asuransi'] ?? 0);
            $potBtn = (int) ($p['pot_btn'] ?? 0);
            $potBpr = (int) ($p['pot_bpr'] ?? 0);
            $potZakat = (int) ($p['pot_zakat_profesi'] ?? 0);

            $totalPotongan = (int) ($payroll->potongan_sanksi_perusahaan ?? 0)
                + (int) ($payroll->potongan_dapenma ?? 0)
                + (int) ($payroll->potongan_bpjs_tenaga_kerja ?? 0)
                + (int) ($payroll->potongan_bpjs_kesehatan ?? 0)
                + (int) ($payroll->potongan_perumahan ?? 0)
                + (int) ($payroll->potongan_pajak ?? 0)
                + (int) ($payroll->potongan_korpri ?? 0)
                + (int) ($payroll->potongan_tunjangan_perusahaan ?? 0)
                + (int) ($payroll->potongan_trandist_pmi_lain ?? 0)
                + $potKoperasi
                + $potDarmawanita
                + $potAir
                + $potKas
                + $potBjb
                + $potBjbs
                + $potAsuransi
                + $potBtn
                + $potBpr
                + $potZakat;

            $totalPendapatan = (int) ($payroll->total_pendapatan ?? 0);
            $gajiBersih = $totalPendapatan - $totalPotongan;

            \Illuminate\Support\Facades\DB::table('payroll')
                ->where('id', $payroll->id)
                ->update([
                    'potongan_koperasi' => $potKoperasi,
                    'potongan_darma_wanita' => $potDarmawanita,
                    'potongan_rekening_air_minum' => $potAir,
                    'potongan_kas' => $potKas,
                    'potongan_bank_bjb' => $potBjb,
                    'potongan_bank_bjbs' => $potBjbs,
                    'potongan_asuransi' => $potAsuransi,
                    'potongan_bank_btn' => $potBtn,
                    'potongan_bank_bpr' => $potBpr,
                    'potongan_zakat_profesi' => $potZakat,
                    'total_potongan' => $totalPotongan,
                    'gaji_bersih' => $gajiBersih,
                    'updated_at' => now(),
                ]);

            // Sinkronkan ke local storage jika ada
            $localFile = storage_path('app/gaji_proses.json');
            if (file_exists($localFile)) {
                $localData = json_decode(file_get_contents($localFile), true);
                if (is_array($localData)) {
                    $changed = false;
                    foreach ($localData as &$row) {
                        if (($row['id'] ?? null) == $payroll->id || ($nik && ($row['nik'] ?? '') === $nik && ($row['bulan'] ?? '') == $bulan && ($row['tahun'] ?? '') == $tahun)) {
                            $row['potongan_koperasi'] = $potKoperasi;
                            $row['potongan_darmawanita'] = $potDarmawanita;
                            $row['potongan_ledeng'] = $potAir;
                            $row['potongan_kas'] = $potKas;
                            $row['potongan_bjb'] = $potBjb;
                            $row['potongan_bjbs'] = $potBjbs;
                            $row['potongan_asuransi'] = $potAsuransi;
                            $row['potongan_btn'] = $potBtn;
                            $row['potongan_bpr'] = $potBpr;
                            $row['potongan_zakat'] = $potZakat;
                            $row['total_potongan'] = $totalPotongan;
                            $row['gaji_bersih'] = $gajiBersih;
                            $changed = true;
                        }
                    }
                    if ($changed) {
                        file_put_contents($localFile, json_encode($localData, JSON_PRETTY_PRINT));
                    }
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('syncToPayroll error: ' . $e->getMessage());
        }
    }

    // ───── BELUM MASUK ─────

    public function belumMasuk(Request $request, string $tipe)
    {
        $this->validateTipe($tipe);
        $bulanParam = $request->query('bulan');
        $tahunParam = $request->query('tahun');
        $bulan = $bulanParam !== null && $bulanParam !== 'all' ? (int) $bulanParam : (int) now()->month;
        $tahun = $tahunParam !== null ? (int) $tahunParam : (int) now()->year;

        $nikSudah = \Illuminate\Support\Facades\DB::table('potongan_keu')
            ->where('tipe', $tipe)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->pluck('nik')
            ->unique()
            ->all();

        $belum = collect($this->pegawaiList())->reject(fn ($p) => in_array($p['nik'], $nikSudah));

        return view('potongan-keu.belum-masuk', [
            'pegawai'   => $belum->values(),
            'tipe'      => $tipe,
            'tipeLabel' => $this->tipeLabels[$tipe],
            'bulan'     => $bulan,
            'tahun'     => $tahun,
        ]);
    }

    // ───── VALIDATION ─────

    protected function validateData(Request $request): array
    {
        $rules = ['pegawai_id' => 'required|integer'];
        foreach ($this->kolom as $k) {
            $rules[$k] = 'nullable|numeric|min:0';
        }
        return $request->validate($rules);
    }
}
