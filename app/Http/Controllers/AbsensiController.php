<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Concerns\HandlesExcelImport;

class AbsensiController extends Controller
{
    use HandlesExcelImport;
    /**
     * Data presensi terintegrasi langsung dengan database Supabase (tabel attendance).
     * Data pegawai ditarik dari PegawaiController yang membaca tabel pegawai.
     */
    public const BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    protected function all(): array
    {
        try {
            $rows = \Illuminate\Support\Facades\DB::table('attendance')
                ->leftJoin('pegawai', 'attendance.pegawai_id', '=', 'pegawai.id')
                ->select('attendance.*', 'pegawai.nik as p_nik', 'pegawai.name as p_name', 'pegawai.unit_kerja as p_unit_kerja')
                ->orderByDesc('attendance.id')
                ->get();
            if ($rows->isNotEmpty()) {
                return $rows->map(function ($r) {
                    $arr = (array) $r;
                    $p = $this->pegawaiById($arr['pegawai_id'] ?? null);
                    $arr['pegawai_id'] = $p['id'] ?? $arr['pegawai_id'];
                    $arr['nik'] = $arr['p_nik'] ?? $p['nik'] ?? '-';
                    $arr['nama'] = $arr['p_name'] ?? $p['nama'] ?? '-';
                    $arr['unit_kerja'] = $arr['p_unit_kerja'] ?? $p['unit_kerja'] ?? '-';
                    $arr['hari_kerja'] = (int) cache()->get('simpeg_hari_kerja', 25);
                    $arr['hadir'] = (int) ($arr['hadir'] ?? 0);
                    $arr['telat'] = (int) ($arr['telat'] ?? 0);
                    $arr['izin'] = (int) ($arr['izin'] ?? 0);
                    $arr['sakit'] = 0;
                    $arr['alpha'] = (int) ($arr['telat'] ?? 0);
                    $arr['keterangan'] = '-';
                    return $arr;
                })->toArray();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('DB read attendance failed: ' . $e->getMessage());
        }

        return [];
    }

    protected function pegawaiList(): array
    {
        // Pegawai berstatus Pensiun (PN) tidak ditampilkan di dropdown pilih pegawai.
        return collect(app(PegawaiController::class)->all())->where('status_peg', '!=', 'PN')->values()->all();
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

    /**
     * Gabungkan data absensi dengan nama/NIK/unit kerja pegawai (kayak JOIN di SQL asli).
     */
    protected function withPegawai(array $rows): array
    {
        return collect($rows)->map(function ($row) {
            $p = $this->pegawaiById($row['pegawai_id']);
            $row['nik'] = $p['nik'] ?? '-';
            $row['nama'] = $p['nama'] ?? '(pegawai tidak ditemukan)';
            $row['unit_kerja'] = $p['unit_kerja'] ?? '-';

            return $row;
        })->all();
    }

    /**
     * SET Hari Kerja - disamakan dengan sistem lama (set_hari_kerja.php).
     */
    public function hariKerjaEdit()
    {
        $hariKerja = cache()->get('simpeg_hari_kerja', 25);

        return view('absensi.hari-kerja', compact('hariKerja'));
    }

    public function hariKerjaUpdate(Request $request)
    {
        $validated = $request->validate([
            'hari_kerja' => 'required|integer|min:1|max:31',
        ]);

        cache()->forever('simpeg_hari_kerja', $validated['hari_kerja']);

        return redirect()->route('absensi.hari-kerja')->with('success', 'Hari kerja berhasil diperbarui.');
    }

    public function index(Request $request)
    {
        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);

        $absensi = collect($this->withPegawai($this->all()))
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->sortBy('nama')
            ->values();

        return view('absensi.index', [
            'absensi' => $absensi,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'bulanList' => self::BULAN,
        ]);
    }

    public function create(Request $request)
    {
        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);

        return view('absensi.create', [
            'pegawaiList' => $this->pegawaiList(),
            'bulanList' => self::BULAN,
            'bulan' => $bulan,
            'tahun' => $tahun,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        $pegawai = $this->pegawaiById($validated['pegawai_id']);
        $dbPegId = $pegawai['db_id'] ?? null;
        if (!$dbPegId && !empty($validated['pegawai_id'])) {
            $dbRow = \Illuminate\Support\Facades\DB::table('pegawai')
                ->where('id', $validated['pegawai_id'])
                ->orWhere('nik', (string) $validated['pegawai_id'])
                ->first();
            $dbPegId = $dbRow?->id;
        }

        if ($dbPegId) {
            try {
                $bulanLabel = self::BULAN[$validated['bulan']] ?? "Bulan {$validated['bulan']}";
                \Illuminate\Support\Facades\DB::table('attendance')->insert([
                    'pegawai_id' => $dbPegId,
                    'tahun' => $validated['tahun'],
                    'bulan' => $validated['bulan'],
                    'bulan_label' => "{$bulanLabel} {$validated['tahun']}",
                    'hadir' => $validated['hadir'],
                    'telat' => $validated['alpha'] ?? 0,
                    'izin' => ($validated['izin'] ?? 0) + ($validated['sakit'] ?? 0),
                    'created_at' => now(),
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('DB insert attendance failed: ' . $e->getMessage());
            }
        }

        return redirect()->route('absensi.index', ['bulan' => $validated['bulan'], 'tahun' => $validated['tahun']])
            ->with('success', 'Data absensi berhasil ditambahkan ke database.');
    }

    public function edit(int $id)
    {
        $absensi = collect($this->all())->firstWhere('id', $id);
        abort_if(! $absensi, 404);

        return view('absensi.edit', [
            'absensi' => $absensi,
            'pegawaiList' => $this->pegawaiList(),
            'bulanList' => self::BULAN,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $validated = $this->validateData($request);

        $pegawai = $this->pegawaiById($validated['pegawai_id']);
        $dbPegId = $pegawai['db_id'] ?? null;
        if (!$dbPegId && !empty($validated['pegawai_id'])) {
            $dbRow = \Illuminate\Support\Facades\DB::table('pegawai')
                ->where('id', $validated['pegawai_id'])
                ->orWhere('nik', (string) $validated['pegawai_id'])
                ->first();
            $dbPegId = $dbRow?->id;
        }

        try {
            $bulanLabel = self::BULAN[$validated['bulan']] ?? "Bulan {$validated['bulan']}";
            \Illuminate\Support\Facades\DB::table('attendance')->where('id', $id)->update([
                'pegawai_id' => $dbPegId ?: \Illuminate\Support\Facades\DB::raw('pegawai_id'),
                'tahun' => $validated['tahun'],
                'bulan' => $validated['bulan'],
                'bulan_label' => "{$bulanLabel} {$validated['tahun']}",
                'hadir' => $validated['hadir'],
                'telat' => $validated['alpha'] ?? 0,
                'izin' => ($validated['izin'] ?? 0) + ($validated['sakit'] ?? 0),
            ]);
        } catch (\Throwable $e) {}

        return redirect()->route('absensi.index', ['bulan' => $validated['bulan'], 'tahun' => $validated['tahun']])
            ->with('success', 'Data absensi berhasil diperbarui.');
    }

    public function destroy(Request $request, int $id)
    {
        $item = collect($this->all())->firstWhere('id', $id);
        $bulan = $request->input('bulan', $request->query('bulan', $item['bulan'] ?? null));
        $tahun = $request->input('tahun', $request->query('tahun', $item['tahun'] ?? null));

        try {
            \Illuminate\Support\Facades\DB::table('attendance')->where('id', $id)->delete();
        } catch (\Throwable $e) {}

        $params = [];
        if ($bulan) {
            $params['bulan'] = $bulan;
        }
        if ($tahun) {
            $params['tahun'] = $tahun;
        }

        return redirect()->route('absensi.index', $params)->with('success', 'Data absensi berhasil dihapus.');
    }

    public function destroyAll(Request $request)
    {
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');

        $deletedCount = 0;
        try {
            $query = \Illuminate\Support\Facades\DB::table('attendance');
            if ($bulan) {
                $query->where('bulan', (int) $bulan);
            }
            if ($tahun) {
                $query->where('tahun', (int) $tahun);
            }
            $deletedCount = $query->delete();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Destroy all attendance failed: ' . $e->getMessage());
        }

        $params = [];
        if ($bulan) {
            $params['bulan'] = (int) $bulan;
        }
        if ($tahun) {
            $params['tahun'] = (int) $tahun;
        }

        return redirect()->route('absensi.index', $params)->with('success', "{$deletedCount} data absensi berhasil dihapus semuanya.");
    }

    /**
     * Halaman laporan - read-only, bisa dicetak (window.print via tombol di view).
     */
    public function laporan(Request $request)
    {
        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);

        $absensi = collect($this->withPegawai($this->all()))
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->sortBy('nama')
            ->values();

        $rekap = [
            'total_pegawai' => $absensi->count(),
            'total_hadir' => $absensi->sum('hadir'),
            'total_sakit' => $absensi->sum('sakit'),
            'total_izin' => $absensi->sum('izin'),
            'total_alpha' => $absensi->sum('alpha'),
        ];

        return view('absensi.laporan', [
            'absensi' => $absensi,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'bulanList' => self::BULAN,
            'rekap' => $rekap,
        ]);
    }

    /**
     * Unduh Template Excel / CSV Absensi Pegawai.
     */
    public function downloadTemplate(Request $request)
    {
        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);
        $hariKerjaDefault = (int) cache()->get('simpeg_hari_kerja', 25);
        $bulanNama = self::BULAN[$bulan] ?? 'Bulan_' . $bulan;

        $pegawaiList = $this->pegawaiList();
        $filename = "Template_Absensi_Pegawai_{$bulanNama}_{$tahun}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = ['NO', 'NIK', 'NAMA', 'HARI KERJA', 'HADIR', 'SAKIT', 'IZIN', 'ALPHA', 'KETERANGAN'];

        $callback = function () use ($columns, $pegawaiList, $hariKerjaDefault) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM
            fputcsv($handle, $columns);

            $no = 1;
            foreach ($pegawaiList as $p) {
                fputcsv($handle, [
                    $no++,
                    $p['nik'] ?? '',
                    $p['nama'] ?? '',
                    $hariKerjaDefault,
                    $hariKerjaDefault, // Default hadir = hari kerja
                    0, // Sakit
                    0, // Izin
                    0, // Alpha
                    '-', // Keterangan
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Impor Data Absensi dari File Excel (.xlsx / .csv).
     */
    public function importExcel(Request $request)
    {
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '512M');
        @set_time_limit(0);

        $request->validate([
            'file_excel' => 'required|file|max:10240',
            'bulan'      => 'required|integer|between:1,12',
            'tahun'      => 'required|integer|min:2020|max:2100',
            'mode'       => 'nullable|in:update,skip',
        ]);

        $bulan = (int) $request->input('bulan');
        $tahun = (int) $request->input('tahun');
        $mode = $request->input('mode', 'update');
        $bulanLabel = self::BULAN[$bulan] ?? "Bulan {$bulan}";
        $fullBulanLabel = "{$bulanLabel} {$tahun}";

        $rows = $this->readUploadedRows($request->file('file_excel'), 'absensi');
        if (empty($rows)) {
            return back()->with('error', 'Gagal membaca isi file atau file kosong. Pastikan format sesuai template.');
        }

        $headerMap = $this->extractHeaderMap($rows, [
            'hadir', 'hari_kerja', 'alpha', 'telat',
            '07.30_-_08.00', '07.31_-_08.00', '08.01_-_08.30', 'no_in_-_out', 'no_in-out',
        ]);
        if (! $headerMap || ! isset($headerMap['nik'])) {
            return back()->with('error', 'Header kolom NIK tidak ditemukan dalam file. Gunakan template yang disediakan.');
        }

        // Cari indeks kolom pendukung (mendukung baik format Template Web maupun format Rekap Jam Keterlambatan SIMPEG Lama)
        $colNik = $headerMap['nik'];
        $colHariKerja = $this->findHeaderCol($headerMap, ['hari_kerja', 'hk']);
        $colHadir = $this->findHeaderCol($headerMap, ['hadir', 'jml_hadir']);
        $colSakit = $this->findHeaderCol($headerMap, ['sakit', 's']);
        $colIzin = $this->findHeaderCol($headerMap, ['izin', 'i']);
        $colAlpha = $this->findHeaderCol($headerMap, ['alpha', 'telat', 'alpa']);

        // Kolom format keterlambatan SIMPEG lama (Sheet ABSENSI: 07.30-08.00, 08.01-08.30, 08.31-09.00, >09.00, Pulang Sblm 16.00, No In-Out)
        $colTelatA = $this->findHeaderCol($headerMap, ['07.30_-_08.00', '07.31_-_08.00', 'telat_a']);
        $colTelatB = $this->findHeaderCol($headerMap, ['08.01_-_08.30', 'telat_b']);
        $colTelatC = $this->findHeaderCol($headerMap, ['08.31-_09.00', '08.31_-_09.00', 'telat_c']);
        $colTelatD = $this->findHeaderCol($headerMap, ['>09.00', '>_09.00', '>_09.01', '>09.01', 'telat_d']);
        $colPulangCepat = $this->findHeaderCol($headerMap, ['pulang_sblm_16.00', 'pulang_cepat']);
        $colNoInOut = $this->findHeaderCol($headerMap, ['no_in_-_out', 'no_in-out', 'no_in_out']);
        $isLegacyTelatFormat = ($colHadir === null && ($colTelatA !== null || $colTelatB !== null || $colTelatC !== null || $colTelatD !== null || $colNoInOut !== null));

        $hariKerjaDefault = (int) cache()->get('simpeg_hari_kerja', 25);

        // Ambil mapping NIK -> UUID pegawai
        $pegawaiDbMap = \Illuminate\Support\Facades\DB::table('pegawai')->pluck('id', 'nik')->all();

        // Ambil data attendance yang sudah ada di bulan & tahun ini
        $existingRows = \Illuminate\Support\Facades\DB::table('attendance')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get()
            ->keyBy('pegawai_id');

        $importedCount = 0;
        $updatedCount = 0;
        $now = now();

        foreach ($rows as $r) {
            $rawNik = (string) ($r[$colNik] ?? '');
            $nik = $this->normalizeNik($rawNik, $pegawaiDbMap);
            if (empty($nik)) {
                continue;
            }

            $dbPegId = $pegawaiDbMap[$nik] ?? ($pegawaiDbMap['TEMP' . $nik] ?? null);
            if (! $dbPegId) {
                continue;
            }

            if ($isLegacyTelatFormat) {
                $tA = $colTelatA !== null ? (int) preg_replace('/[^0-9]/', '', (string) ($r[$colTelatA] ?? '0')) : 0;
                $tB = $colTelatB !== null ? (int) preg_replace('/[^0-9]/', '', (string) ($r[$colTelatB] ?? '0')) : 0;
                $tC = $colTelatC !== null ? (int) preg_replace('/[^0-9]/', '', (string) ($r[$colTelatC] ?? '0')) : 0;
                $tD = $colTelatD !== null ? (int) preg_replace('/[^0-9]/', '', (string) ($r[$colTelatD] ?? '0')) : 0;
                $pCepat = $colPulangCepat !== null ? (int) preg_replace('/[^0-9]/', '', (string) ($r[$colPulangCepat] ?? '0')) : 0;
                $noIo = $colNoInOut !== null ? (int) preg_replace('/[^0-9]/', '', (string) ($r[$colNoInOut] ?? '0')) : 0;

                $totalTelatKali = $tA + $tB + $tC + $tD + $pCepat + $noIo;
                $hkRow = $colHariKerja !== null ? (int) preg_replace('/[^0-9]/', '', (string) ($r[$colHariKerja] ?? (string) $hariKerjaDefault)) : $hariKerjaDefault;
                $hadir = max(0, $hkRow - $noIo);
                $sakit = 0;
                $izin = 0;
                $alpha = $totalTelatKali;
            } else {
                $hadir = $colHadir !== null ? (int) preg_replace('/[^0-9]/', '', (string)($r[$colHadir] ?? '0')) : 0;
                $sakit = $colSakit !== null ? (int) preg_replace('/[^0-9]/', '', (string)($r[$colSakit] ?? '0')) : 0;
                $izin = $colIzin !== null ? (int) preg_replace('/[^0-9]/', '', (string)($r[$colIzin] ?? '0')) : 0;
                $alpha = $colAlpha !== null ? (int) preg_replace('/[^0-9]/', '', (string)($r[$colAlpha] ?? '0')) : 0;
            }

            if ($existingRows->has($dbPegId)) {
                if ($mode === 'update') {
                    $existing = $existingRows->get($dbPegId);
                    \Illuminate\Support\Facades\DB::table('attendance')
                        ->where('id', $existing->id)
                        ->update([
                            'tahun'       => $tahun,
                            'bulan'       => $bulan,
                            'bulan_label' => $fullBulanLabel,
                            'hadir'       => $hadir,
                            'telat'       => $alpha,
                            'izin'        => $izin + $sakit,
                        ]);
                    $updatedCount++;
                }
            } else {
                $newId = \Illuminate\Support\Facades\DB::table('attendance')->insertGetId([
                    'pegawai_id'  => $dbPegId,
                    'tahun'       => $tahun,
                    'bulan'       => $bulan,
                    'bulan_label' => $fullBulanLabel,
                    'hadir'       => $hadir,
                    'telat'       => $alpha,
                    'izin'        => $izin + $sakit,
                    'created_at'  => $now,
                ]);
                $existingRows->put($dbPegId, (object) ['id' => $newId, 'pegawai_id' => $dbPegId]);
                $importedCount++;
            }
        }

        return redirect()->route('absensi.index', ['bulan' => $bulan, 'tahun' => $tahun])
            ->with('success', "Proses impor selesai! {$importedCount} data baru ditambahkan, {$updatedCount} data diperbarui untuk periode {$fullBulanLabel}.");
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'pegawai_id' => 'required|integer',
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2020|max:2100',
            'hari_kerja' => 'required|integer|min:0|max:31',
            'hadir' => 'required|integer|min:0|max:31',
            'sakit' => 'required|integer|min:0|max:31',
            'izin' => 'required|integer|min:0|max:31',
            'alpha' => 'required|integer|min:0|max:31',
            'keterangan' => 'nullable|string|max:255',
        ]);
    }
}
