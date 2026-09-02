<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use ZipArchive;
use SimpleXMLElement;

class PotonganKeuController extends Controller
{
    /**
     * DATA DUMMY BERBASIS SESSION — meniru pattern DapenmaController.
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

    protected function sessionKey(string $tipe): string
    {
        return "dummy_potongan_{$tipe}";
    }

    protected function seedIfEmpty(string $tipe): void
    {
        if (! session()->has($this->sessionKey($tipe))) {
            $pegawaiList = $this->pegawaiList();
            $seed = [];
            foreach (array_slice($pegawaiList, 0, 4) as $idx => $p) {
                $statusPilihan = ($idx === 0) ? 'kepegawaian' : (($idx === 1) ? 'Y' : 'N');
                $row = [
                    'id'            => $idx + 1,
                    'tipe'          => $tipe,
                    'tgl_potongan'  => now()->toDateString(),
                    'nik'           => $p['nik'],
                    'pegawai_id'    => $p['id'],
                    'petugas_entri' => 'Admin Keuangan',
                    'tgl_update'    => now()->toDateString(),
                    'status'        => $statusPilihan,
                    'disetujui_kepegawaian_oleh' => ($statusPilihan !== 'N') ? 'SDM/Kepegawaian' : null,
                    'tgl_setuju_kepegawaian'    => ($statusPilihan !== 'N') ? now()->format('d/m/Y H:i') : null,
                    'disetujui_oleh'             => ($statusPilihan === 'Y') ? 'Manajer Keuangan' : null,
                ];
                foreach ($this->kolom as $k) {
                    $row[$k] = rand(0, 500) * 1000;
                }
                $seed[] = $row;
            }
            session()->put($this->sessionKey($tipe), $seed);
        }
    }

    protected function all(string $tipe): array
    {
        $this->seedIfEmpty($tipe);
        return session($this->sessionKey($tipe), []);
    }

    protected function save(string $tipe, array $data): void
    {
        session()->put($this->sessionKey($tipe), $data);
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
        if ($pegawaiMap) {
            $p = $pegawaiMap->get($row['nik']);
        } else {
            $p = collect($this->pegawaiList())->firstWhere('nik', $row['nik']);
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

        $allItems = collect($this->all($tipe))
            ->map(fn ($row) => $this->withCalculated($row, $pegawaiMap))
            ->sortBy('nik')
            ->values();

        $items = $allItems;
        if ($keyword !== '') {
            $lowerKw = strtolower($keyword);
            $items = $items->filter(function ($r) use ($lowerKw) {
                return str_contains(strtolower($r['nik'] ?? ''), $lowerKw)
                    || str_contains(strtolower($r['nama'] ?? ''), $lowerKw)
                    || str_contains(strtolower($r['jabatan'] ?? ''), $lowerKw)
                    || str_contains(strtolower($r['unit_kerja'] ?? ''), $lowerKw);
            })->values();
        }

        $totalPegawai = count($pegawaiList);
        $sudahMasuk   = $allItems->count();
        $belumMasuk   = max(0, $totalPegawai - $sudahMasuk);

        return view('potongan-keu.index', [
            'items'       => $items,
            'keyword'     => $keyword,
            'totalItems'  => $allItems->count(),
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

        $data = $this->all($tipe);
        $newId = $data ? max(array_column($data, 'id')) + 1 : 1;

        $row = [
            'id'            => $newId,
            'tipe'          => $tipe,
            'tgl_potongan'  => now()->toDateString(),
            'nik'           => $peg['nik'],
            'pegawai_id'    => $peg['id'],
            'petugas_entri' => session('simpeg_user.nama_peg', 'Admin'),
            'tgl_update'    => now()->toDateString(),
            'status'        => 'N',
            'disetujui_kepegawaian_oleh' => null,
            'tgl_setuju_kepegawaian'    => null,
            'disetujui_oleh'             => null,
        ];
        foreach ($this->kolom as $k) {
            $row[$k] = (int) ($validated[$k] ?? 0);
        }

        $data[] = $row;
        $this->save($tipe, $data);

        return redirect()->route('potongan-keu.index', $tipe)->with('success', 'Potongan berhasil ditambahkan.');
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

        $data = collect($this->all($tipe))->map(function ($row) use ($id, $validated, $peg) {
            if ($row['id'] === $id) {
                foreach ($this->kolom as $k) {
                    $row[$k] = (int) ($validated[$k] ?? 0);
                }
                $row['nik'] = $peg['nik'];
                $row['pegawai_id'] = $peg['id'];
                $row['petugas_entri'] = session('simpeg_user.nama_peg', 'Admin');
                $row['tgl_update'] = now()->toDateString();
            }
            return $row;
        })->all();

        $this->save($tipe, $data);
        return redirect()->route('potongan-keu.index', $tipe)->with('success', 'Potongan berhasil diperbarui.');
    }

    public function destroy(string $tipe, int $id)
    {
        $this->validateTipe($tipe);
        $data = collect($this->all($tipe))->reject(fn ($r) => $r['id'] === $id)->values()->all();
        $this->save($tipe, $data);
        return redirect()->route('potongan-keu.index', $tipe)->with('success', 'Potongan berhasil dihapus.');
    }

    // ───── FITUR IMPORT EXCEL / CSV MASSAL ─────

    /**
     * Unduh template file spreadsheet (.csv) siap pakai untuk import potongan.
     */
    public function downloadTemplate(string $tipe)
    {
        $this->validateTipe($tipe);
        $pegawaiList = $this->pegawaiList();

        $filename = "Template_Set_Potongan_" . strtoupper($tipe) . "_" . date('Ymd') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = ['NIK', 'Nama Pegawai'];
        foreach ($this->kolom as $k) {
            $columns[] = $this->kolomLabels[$k];
        }

        $callback = function () use ($columns, $pegawaiList) {
            $handle = fopen('php://output', 'w');
            // Add UTF-8 BOM for Excel compatibility
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $columns);

            foreach ($pegawaiList as $p) {
                $row = [$p['nik'], $p['nama']];
                // Isi nominal sample / 0
                foreach (range(1, count($this->kolom)) as $i) {
                    $row[] = '0';
                }
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
            $rows = $this->parseXlsx($file->getRealPath());
        } else {
            // Coba parse sebagai CSV jika extension lain
            $rows = $this->parseCsv($file->getRealPath());
        }

        if (empty($rows)) {
            return back()->with('error', 'Gagal membaca isi file atau file kosong. Pastikan format sesuai template.');
        }

        // Mapping index kolom dari baris header (baris pertama)
        $header = array_shift($rows);
        $headerMap = [];
        foreach ($header as $colIdx => $colName) {
            $cleanName = strtolower(trim((string) $colName));
            if (str_contains($cleanName, 'nik')) {
                $headerMap['nik'] = $colIdx;
            }
            foreach ($this->kolomLabels as $colKey => $label) {
                if (str_contains($cleanName, strtolower($label)) || str_contains($cleanName, strtolower($colKey))) {
                    $headerMap[$colKey] = $colIdx;
                }
            }
        }

        // Jika header tidak lengkap, fallback ke urutan kolom default
        if (! isset($headerMap['nik'])) {
            $headerMap['nik'] = 0;
            $idx = 2; // Mulai setelah NIK dan Nama
            foreach ($this->kolom as $k) {
                $headerMap[$k] = $idx++;
            }
        }

        $pegawaiList = $this->pegawaiList();
        $pegawaiMap = collect($pegawaiList)->keyBy('nik');

        $existingData = $this->all($tipe);
        $existingMap = collect($existingData)->keyBy('nik')->all();

        $maxId = $existingData ? max(array_column($existingData, 'id')) : 0;
        $importedCount = 0;
        $updatedCount = 0;

        foreach ($rows as $r) {
            $nik = trim((string) ($r[$headerMap['nik']] ?? ''));
            // Hapus karakter non-digit/kutip
            $nik = preg_replace('/[^0-9]/', '', $nik);

            if (empty($nik) || ! isset($pegawaiMap[$nik])) {
                continue;
            }

            $peg = $pegawaiMap[$nik];
            $values = [];
            foreach ($this->kolom as $k) {
                $colIdx = $headerMap[$k] ?? null;
                $valStr = $colIdx !== null ? (string) ($r[$colIdx] ?? '0') : '0';
                $cleanVal = (int) preg_replace('/[^0-9]/', '', $valStr);
                $values[$k] = $cleanVal;
            }

            if (isset($existingMap[$nik])) {
                if ($mode === 'update') {
                    // Update data yang ada
                    $existingItem = $existingMap[$nik];
                    foreach ($values as $k => $v) {
                        $existingItem[$k] = $v;
                    }
                    $existingItem['tgl_update'] = now()->toDateString();
                    $existingItem['petugas_entri'] = session('simpeg_user.nama_peg', 'Admin (Excel Import)');
                    $existingMap[$nik] = $existingItem;
                    $updatedCount++;
                }
            } else {
                // Tambah data baru
                $maxId++;
                $newRow = [
                    'id'            => $maxId,
                    'tipe'          => $tipe,
                    'tgl_potongan'  => now()->toDateString(),
                    'nik'           => $nik,
                    'pegawai_id'    => $peg['id'],
                    'petugas_entri' => session('simpeg_user.nama_peg', 'Admin (Excel Import)'),
                    'tgl_update'    => now()->toDateString(),
                    'status'        => 'N',
                    'disetujui_kepegawaian_oleh' => null,
                    'tgl_setuju_kepegawaian'    => null,
                    'disetujui_oleh'             => null,
                ];
                foreach ($values as $k => $v) {
                    $newRow[$k] = $v;
                }
                $existingMap[$nik] = $newRow;
                $importedCount++;
            }
        }

        $finalData = array_values($existingMap);
        $this->save($tipe, $finalData);

        $totalTersimpan = $importedCount + $updatedCount;
        return redirect()->route('potongan-keu.index', $tipe)->with(
            'success',
            "Berhasil mengimpor potongan {$this->tipeLabels[$tipe]} dari file Excel: {$importedCount} data baru ditambahkan, {$updatedCount} data diperbarui."
        );
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
        $zip = new ZipArchive();
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
                        $rowVals[] = $val;
                    }
                    if (count(array_filter($rowVals, fn ($v) => trim((string)$v) !== '')) > 0) {
                        $rows[] = $rowVals;
                    }
                }
            }
        }

        $zip->close();
        return $rows;
    }

    // ───── PROSES TERBIT POTONGAN (REALTIME & BERJENJANG) ─────

    public function terbitIndex(string $tipe)
    {
        $this->validateTipe($tipe);
        $pegawaiList = $this->pegawaiList();
        $pegawaiMap = collect($pegawaiList)->keyBy('nik');

        $allItems = collect($this->all($tipe))
            ->map(fn ($row) => $this->withCalculated($row, $pegawaiMap))
            ->sortBy('nik')
            ->values();

        // Siap Diterbitkan = status 'kepegawaian' atau 'N'
        $items = $allItems->where('status', '!=', 'Y')->values();
        $sudahDiterbitkan = $allItems->where('status', '===', 'Y')->count();
        $disetujuiKepegawaian = $allItems->where('status', '===', 'kepegawaian')->count();
        $menungguKepegawaian = $allItems->where('status', '===', 'N')->count();

        $totals = [];
        foreach ($this->kolom as $k) {
            $totals[$k] = $items->sum($k);
        }
        $totals['grand_total'] = $items->sum('total');

        return view('potongan-keu.terbit', [
            'items'                => $items,
            'allItems'             => $allItems,
            'sudahDiterbitkan'     => $sudahDiterbitkan,
            'disetujuiKepegawaian' => $disetujuiKepegawaian,
            'menungguKepegawaian'  => $menungguKepegawaian,
            'tipe'                 => $tipe,
            'tipeLabel'            => $this->tipeLabels[$tipe],
            'kolom'                => $this->kolom,
            'kolomLabels'          => $this->kolomLabels,
            'totals'               => $totals,
        ]);
    }

    /**
     * Endpoint API JSON untuk Polling Realtime Status Terbit Potongan.
     */
    public function realtimeStatus(string $tipe)
    {
        $this->validateTipe($tipe);
        $pegawaiList = $this->pegawaiList();
        $pegawaiMap = collect($pegawaiList)->keyBy('nik');

        $allItems = collect($this->all($tipe))
            ->map(fn ($row) => $this->withCalculated($row, $pegawaiMap))
            ->sortBy('nik')
            ->values();

        $itemsPending = $allItems->where('status', '!=', 'Y')->values();
        $sudahDiterbitkan = $allItems->where('status', '===', 'Y')->count();
        $disetujuiKepegawaian = $allItems->where('status', '===', 'kepegawaian')->count();
        $menungguKepegawaian = $allItems->where('status', '===', 'N')->count();

        $totals = [];
        foreach ($this->kolom as $k) {
            $totals[$k] = $itemsPending->sum($k);
        }
        $totals['grand_total'] = $itemsPending->sum('total');

        return response()->json([
            'success'              => true,
            'timestamp'            => now()->format('H:i:s'),
            'totalPending'         => $itemsPending->count(),
            'sudahDiterbitkan'     => $sudahDiterbitkan,
            'disetujuiKepegawaian' => $disetujuiKepegawaian,
            'menungguKepegawaian'  => $menungguKepegawaian,
            'grandTotalFormatted'  => 'Rp ' . number_format($totals['grand_total'], 0, ',', '.'),
            'items'                => $allItems->map(function ($it) {
                $statusLabel = 'Menunggu Kepegawaian';
                $statusClass = 'badge-warning';
                if ($it['status'] === 'kepegawaian') {
                    $statusLabel = 'Disetujui Kepegawaian';
                    $statusClass = 'badge-success';
                } elseif ($it['status'] === 'Y' || $it['status'] === 'terbit') {
                    $statusLabel = 'Diterbitkan Final';
                    $statusClass = 'badge-primary';
                }
                return [
                    'id'               => $it['id'],
                    'nik'              => $it['nik'],
                    'nama'             => $it['nama'],
                    'status'           => $it['status'],
                    'statusLabel'      => $statusLabel,
                    'statusClass'      => $statusClass,
                    'tgl_setuju'       => $it['tgl_setuju_kepegawaian'] ?? null,
                    'disetujui_oleh'   => $it['disetujui_kepegawaian_oleh'] ?? $it['disetujui_oleh'] ?? '-',
                    'totalFormatted'   => 'Rp ' . number_format($it['total'], 0, ',', '.'),
                ];
            }),
        ]);
    }

    /**
     * Aksi Persetujuan dari Kepegawaian (SDM).
     */
    public function setujuiKepegawaian(Request $request, string $tipe)
    {
        $this->validateTipe($tipe);
        $targetId = $request->input('id');
        $approver = session('simpeg_user.nama_peg', 'SDM / Kepegawaian');

        $data = collect($this->all($tipe))->map(function ($row) use ($targetId, $approver) {
            if ($targetId ? ($row['id'] == $targetId) : ($row['status'] === 'N')) {
                $row['status'] = 'kepegawaian';
                $row['disetujui_kepegawaian_oleh'] = $approver;
                $row['tgl_setuju_kepegawaian'] = now()->format('d/m/Y H:i');
                $row['tgl_update'] = now()->toDateString();
            }
            return $row;
        })->all();

        $this->save($tipe, $data);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Status berhasil disetujui oleh Kepegawaian.']);
        }

        return redirect()->route('potongan-keu.terbit', $tipe)->with('success', 'Potongan berhasil disetujui oleh Kepegawaian (SDM). Status kini Realtime: Siap Diterbitkan.');
    }

    /**
     * Aksi Penerbitan Final dari Keuangan.
     */
    public function terbitkan(Request $request, string $tipe)
    {
        $this->validateTipe($tipe);
        $targetId = $request->input('id');
        $approverNik = session('simpeg_user.nik', config('simpeg_approval.keuangan', '0'));
        $approverName = session('simpeg_user.nama_peg', 'Keuangan');

        $data = collect($this->all($tipe))->map(function ($row) use ($targetId, $approverNik, $approverName) {
            if ($targetId ? ($row['id'] == $targetId) : ($row['status'] !== 'Y')) {
                $row['status'] = 'Y';
                $row['disetujui_oleh'] = $approverName . ' (' . $approverNik . ')';
                $row['tgl_update'] = now()->toDateString();
            }
            return $row;
        })->all();

        $this->save($tipe, $data);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Potongan berhasil diterbitkan & disetujui secara final.']);
        }

        return redirect()->route('potongan-keu.terbit', $tipe)->with('success', 'Semua potongan ' . strtolower($this->tipeLabels[$tipe]) . ' berhasil diterbitkan dan disetujui.');
    }

    // ───── BELUM MASUK ─────

    public function belumMasuk(string $tipe)
    {
        $this->validateTipe($tipe);
        $nikSudah = collect($this->all($tipe))->pluck('nik')->unique()->all();
        $belum = collect($this->pegawaiList())->reject(fn ($p) => in_array($p['nik'], $nikSudah));

        return view('potongan-keu.belum-masuk', [
            'pegawai'   => $belum->values(),
            'tipe'      => $tipe,
            'tipeLabel' => $this->tipeLabels[$tipe],
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
