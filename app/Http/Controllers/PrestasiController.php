<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Concerns\HandlesExcelImport;

class PrestasiController extends Controller
{
    use HandlesExcelImport;
    /**
     * Modul Set Prestasi Kerja Bulanan & Sinkronisasi Lembur.
     * Terhubung langsung dengan tabel prestasi dan lembur di database Supabase PostgreSQL.
     *
     * "Prestasi" di sini BEDA dengan "Prestasi" di Data Pegawai (riwayat
     * penghargaan pribadi). Ini adalah rekap prestasi kerja bulanan yang
     * dipakai untuk perhitungan gaji - disamakan dengan sistem lama
     * (set_prestasi_pegawai.php / tambah_prestasi_gaji.php):
     *   tbl_prestasi (id, id_pegawai, nik, tgl_prestasi, karya, absensi,
     *                 alpha, izin_ket, izin_non_ket, sakit_ket, sakit_non_ket,
     *                 dinas_luar, cuti, alasan_cuti, jam_lembur,
     *                 nominal_lembur_harian, nominal_lembur)
     *
     * Rate lembur per jam = Rp 9.375 (nilai tetap dari sistem lama),
     * nominal_lembur dihitung otomatis: jam_lembur x 9375.
     *
     * Catatan: kolom nominal_makan & nominal_transport ada di query insert
     * sistem lama tapi tidak pernah diisi dari form manapun (variabel
     * undefined / bug peninggalan) - sengaja tidak dibawa ke versi ini.
     */
    public const RATE_LEMBUR_PER_JAM = 9375;

    protected function all(): array
    {
        try {
            $rows = \Illuminate\Support\Facades\DB::table('prestasi')->orderByDesc('id')->get();
            return $rows->map(function ($r) {
                $arr = (array) $r;
                $decoded = null;
                if (!empty($arr['keterangan']) && str_starts_with(trim($arr['keterangan']), '{')) {
                    $decoded = json_decode($arr['keterangan'], true);
                }
                if (is_array($decoded)) {
                    $arr = array_merge($arr, $decoded);
                }
                $arr['karya'] = $arr['karya'] ?? $arr['judul'] ?? '-';
                $arr['absensi'] = $arr['absensi'] ?? $arr['tingkat'] ?? 'Baik';
                $arr['alpha'] = $arr['alpha'] ?? 0;
                $arr['izin_ket'] = $arr['izin_ket'] ?? 0;
                $arr['izin_non_ket'] = $arr['izin_non_ket'] ?? 0;
                $arr['sakit_ket'] = $arr['sakit_ket'] ?? 0;
                $arr['sakit_non_ket'] = $arr['sakit_non_ket'] ?? 0;
                $arr['dinas_luar'] = $arr['dinas_luar'] ?? 0;
                $arr['cuti'] = $arr['cuti'] ?? 0;
                $arr['alasan_cuti'] = $arr['alasan_cuti'] ?? '';
                $arr['jam_lembur'] = (float) ($arr['jam_lembur'] ?? 0);
                $arr['nominal_lembur'] = (float) ($arr['nominal_lembur'] ?? ($arr['jam_lembur'] * self::RATE_LEMBUR_PER_JAM));
                return $arr;
            })->toArray();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('DB fetch all prestasi failed: ' . $e->getMessage());
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

    protected function withCalculated(array $row): array
    {
        $p = $this->pegawaiById($row['pegawai_id'] ?? null);
        $row['nik'] = $p['nik'] ?? ($row['nik'] ?? '-');
        $row['nama'] = $p['nama'] ?? ($row['nama_pegawai'] ?? '(pegawai tidak ditemukan)');
        $row['karya'] = $row['karya'] ?? ($row['judul'] ?? '-');
        $row['absensi'] = $row['absensi'] ?? ($row['tingkat'] ?? '-');
        $row['jam_lembur'] = (float) ($row['jam_lembur'] ?? 0);
        $row['nominal_lembur_harian'] = self::RATE_LEMBUR_PER_JAM;
        $row['nominal_lembur'] = $row['nominal_lembur'] ?? ($row['jam_lembur'] * self::RATE_LEMBUR_PER_JAM);
        $row['tanggal'] = $row['tanggal'] ?? ($row['created_at'] ?? now()->toDateString());
        $row['alpha'] = $row['alpha'] ?? 0;
        $row['izin_ket'] = $row['izin_ket'] ?? 0;
        $row['izin_non_ket'] = $row['izin_non_ket'] ?? 0;
        $row['sakit_ket'] = $row['sakit_ket'] ?? 0;
        $row['sakit_non_ket'] = $row['sakit_non_ket'] ?? 0;
        $row['dinas_luar'] = $row['dinas_luar'] ?? 0;
        $row['cuti'] = $row['cuti'] ?? 0;
        $row['alasan_cuti'] = $row['alasan_cuti'] ?? '';

        return $row;
    }

    public function index()
    {
        $prestasi = collect($this->all())
            ->map(fn ($row) => $this->withCalculated($row))
            ->sortByDesc('tanggal')
            ->values();

        return view('prestasi.index', compact('prestasi'));
    }

    /**
     * Halaman Laporan (read-only, format cetak) - dipisah dari index()
     * yang jadi halaman kelola/SET. Data sumbernya sama, tampilannya beda.
     */
    public function laporan()
    {
        $prestasi = collect($this->all())
            ->map(fn ($row) => $this->withCalculated($row))
            ->sortByDesc('tanggal')
            ->values();

        return view('prestasi.laporan', compact('prestasi'));
    }

    public function create()
    {
        return view('prestasi.create', ['pegawaiList' => $this->pegawaiList()]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        // Cari UUID db_id pegawai berdasarkan ID input
        $pegawai = $this->pegawaiById($validated['pegawai_id']);
        $dbPegId = $pegawai['db_id'] ?? null;
        if (!$dbPegId && !empty($validated['pegawai_id'])) {
            $dbRow = \Illuminate\Support\Facades\DB::table('pegawai')
                ->where('id', $validated['pegawai_id'])
                ->orWhere('nik', (string) $validated['pegawai_id'])
                ->first();
            $dbPegId = $dbRow?->id;
        }

        if (!$dbPegId) {
            return back()->withInput()->withErrors(['pegawai_id' => 'Pegawai tidak valid atau belum terdaftar di database.']);
        }

        $jamLembur = (float) ($validated['jam_lembur'] ?? 0);
        $nominalLembur = (int) round($jamLembur * self::RATE_LEMBUR_PER_JAM);

        $meta = [
            'desc' => "Karya: {$validated['karya']}, Absensi: {$validated['absensi']}" . ($jamLembur > 0 ? ", Lembur: {$jamLembur} jam (Rp " . number_format($nominalLembur, 0, ',', '.') . ")" : ''),
            'karya' => $validated['karya'],
            'absensi' => $validated['absensi'],
            'jam_lembur' => $jamLembur,
            'nominal_lembur' => $nominalLembur,
            'alpha' => (int) ($validated['alpha'] ?? 0),
            'izin_ket' => (int) ($validated['izin_ket'] ?? 0),
            'izin_non_ket' => (int) ($validated['izin_non_ket'] ?? 0),
            'sakit_ket' => (int) ($validated['sakit_ket'] ?? 0),
            'sakit_non_ket' => (int) ($validated['sakit_non_ket'] ?? 0),
            'dinas_luar' => (int) ($validated['dinas_luar'] ?? 0),
            'cuti' => (int) ($validated['cuti'] ?? 0),
            'alasan_cuti' => $validated['alasan_cuti'] ?? '',
        ];

        // 1. Simpan ke tabel prestasi di PostgreSQL
        \Illuminate\Support\Facades\DB::table('prestasi')->insert([
            'pegawai_id' => $dbPegId,
            'judul' => $validated['karya'],
            'tanggal' => $validated['tanggal'],
            'keterangan' => json_encode($meta),
            'tingkat' => $validated['absensi'] ?? 'Perusahaan',
            'created_at' => now(),
        ]);

        // 2. Sinkron ke tabel lembur, payroll, dan insentif
        static::syncPrestasiLemburToTables($dbPegId, $jamLembur, $nominalLembur, $validated['tanggal']);

        // 3. Notifikasi OneSignal & Lonceng Flutter saat Lembur Dicatat
        if ($jamLembur > 0) {
            $tglCarbon = \Illuminate\Support\Carbon::parse($validated['tanggal']);
            $periodeLembur = ($tglCarbon->translatedFormat('F') ?: $tglCarbon->format('F')) . ' ' . $tglCarbon->year;
            $pesanLembur = "Data lembur Anda sebesar {$jamLembur} jam (Rp " . number_format($nominalLembur, 0, ',', '.') . ") periode {$periodeLembur} telah dicatat oleh SDM.";
            $targetNik = $pegawai['nik'] ?? null;
            if (!$targetNik && $dbPegId) {
                $targetNik = \Illuminate\Support\Facades\DB::table('pegawai')->where('id', $dbPegId)->value('nik');
            }

            if ($targetNik) {
                try {
                    \App\Services\OneSignalService::kirimNotifikasiPegawai(
                        $targetNik,
                        '⏰ Data Lembur Dicatat',
                        $pesanLembur,
                        ['type' => 'lembur', 'periode' => $periodeLembur]
                    );
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('OneSignal lembur notif failed: ' . $e->getMessage());
                }
            }
        }

        // 4. Clear cache pegawai
        \Illuminate\Support\Facades\Cache::forget('simpeg_all_pegawai_list');
        \Illuminate\Support\Facades\Cache::forget('pegawai_master_cache');
        \Illuminate\Support\Facades\Cache::forget('simpeg_dashboard_stats');

        return redirect()->route('prestasi.index')->with('success', 'Data prestasi pegawai berhasil ditambahkan dan tersimpan ke database.');
    }

    public function edit(int $id)
    {
        $prestasi = collect($this->all())->firstWhere('id', $id);
        abort_if(! $prestasi, 404);

        $p = $this->pegawaiById($prestasi['pegawai_id'] ?? null);
        if ($p) {
            $prestasi['pegawai_id'] = $p['id'];
        }

        return view('prestasi.edit', [
            'prestasi' => $prestasi,
            'pegawaiList' => $this->pegawaiList(),
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

        $jamLembur = (float) ($validated['jam_lembur'] ?? 0);
        $nominalLembur = (int) round($jamLembur * self::RATE_LEMBUR_PER_JAM);

        $meta = [
            'desc' => "Karya: {$validated['karya']}, Absensi: {$validated['absensi']}" . ($jamLembur > 0 ? ", Lembur: {$jamLembur} jam (Rp " . number_format($nominalLembur, 0, ',', '.') . ")" : ''),
            'karya' => $validated['karya'],
            'absensi' => $validated['absensi'],
            'jam_lembur' => $jamLembur,
            'nominal_lembur' => $nominalLembur,
            'alpha' => (int) ($validated['alpha'] ?? 0),
            'izin_ket' => (int) ($validated['izin_ket'] ?? 0),
            'izin_non_ket' => (int) ($validated['izin_non_ket'] ?? 0),
            'sakit_ket' => (int) ($validated['sakit_ket'] ?? 0),
            'sakit_non_ket' => (int) ($validated['sakit_non_ket'] ?? 0),
            'dinas_luar' => (int) ($validated['dinas_luar'] ?? 0),
            'cuti' => (int) ($validated['cuti'] ?? 0),
            'alasan_cuti' => $validated['alasan_cuti'] ?? '',
        ];

        \Illuminate\Support\Facades\DB::table('prestasi')->where('id', $id)->update([
            'pegawai_id' => $dbPegId ?: \Illuminate\Support\Facades\DB::raw('pegawai_id'),
            'judul' => $validated['karya'],
            'tanggal' => $validated['tanggal'],
            'keterangan' => json_encode($meta),
            'tingkat' => $validated['absensi'] ?? 'Perusahaan',
        ]);

        if ($dbPegId) {
            static::syncPrestasiLemburToTables($dbPegId, $jamLembur, $nominalLembur, $validated['tanggal']);

            if ($jamLembur > 0) {
                $tglCarbon = \Illuminate\Support\Carbon::parse($validated['tanggal']);
                $periodeLembur = ($tglCarbon->translatedFormat('F') ?: $tglCarbon->format('F')) . ' ' . $tglCarbon->year;
                $pesanLembur = "Data lembur Anda sebesar {$jamLembur} jam (Rp " . number_format($nominalLembur, 0, ',', '.') . ") periode {$periodeLembur} telah diperbarui oleh SDM.";
                $targetNik = $pegawai['nik'] ?? null;
                if (!$targetNik && $dbPegId) {
                    $targetNik = \Illuminate\Support\Facades\DB::table('pegawai')->where('id', $dbPegId)->value('nik');
                }

                if ($targetNik) {
                    try {
                        \App\Services\OneSignalService::kirimNotifikasiPegawai(
                            $targetNik,
                            '⏰ Data Lembur Diperbarui',
                            $pesanLembur,
                            ['type' => 'lembur', 'periode' => $periodeLembur]
                        );
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('OneSignal lembur update notif failed: ' . $e->getMessage());
                    }
                }
            }
        }

        \Illuminate\Support\Facades\Cache::forget('simpeg_all_pegawai_list');
        \Illuminate\Support\Facades\Cache::forget('pegawai_master_cache');
        \Illuminate\Support\Facades\Cache::forget('simpeg_dashboard_stats');

        return redirect()->route('prestasi.index')->with('success', 'Data prestasi pegawai berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        try {
            $row = \Illuminate\Support\Facades\DB::table('prestasi')->where('id', $id)->first();
            if ($row) {
                \Illuminate\Support\Facades\DB::table('prestasi')->where('id', $id)->delete();
                if (!empty($row->pegawai_id)) {
                    static::syncPrestasiLemburToTables($row->pegawai_id, 0, 0, $row->tanggal ?? now()->toDateString());
                }
            }
        } catch (\Throwable $e) {}

        \Illuminate\Support\Facades\Cache::forget('simpeg_all_pegawai_list');
        \Illuminate\Support\Facades\Cache::forget('pegawai_master_cache');
        \Illuminate\Support\Facades\Cache::forget('simpeg_dashboard_stats');

        return redirect()->route('prestasi.index')->with('success', 'Data prestasi berhasil dihapus.');
    }

    public function destroyAll()
    {
        $deletedCount = 0;
        try {
            $rows = \Illuminate\Support\Facades\DB::table('prestasi')->get();
            $deletedCount = $rows->count();
            \Illuminate\Support\Facades\DB::table('prestasi')->delete();
            foreach ($rows as $row) {
                if (! empty($row->pegawai_id)) {
                    static::syncPrestasiLemburToTables($row->pegawai_id, 0, 0, $row->tanggal ?? now()->toDateString());
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Destroy all prestasi failed: ' . $e->getMessage());
        }

        \Illuminate\Support\Facades\Cache::forget('simpeg_all_pegawai_list');
        \Illuminate\Support\Facades\Cache::forget('pegawai_master_cache');
        \Illuminate\Support\Facades\Cache::forget('simpeg_dashboard_stats');

        return redirect()->route('prestasi.index')->with('success', "{$deletedCount} data prestasi berhasil dihapus semuanya.");
    }

    public static function syncPrestasiLemburToTables(string $dbPegId, float $jamLembur, int $nominalLembur, string $tanggal): void
    {
        try {
            $tglCarbon = \Illuminate\Support\Carbon::parse($tanggal);
            $bulan = (int) $tglCarbon->month;
            $tahun = (int) $tglCarbon->year;
            $bulanNama = AbsensiController::BULAN[$bulan] ?? $tglCarbon->translatedFormat('F');
            $periodeLembur = "{$bulanNama} {$tahun}";

            // 1. Update atau insert di tabel lembur resmi
            if ($jamLembur > 0 || $nominalLembur > 0) {
                \Illuminate\Support\Facades\DB::table('lembur')->updateOrInsert(
                    ['pegawai_id' => $dbPegId, 'bulan' => $periodeLembur],
                    [
                        'jam_lembur' => (int) round($jamLembur),
                        'uang_lembur' => $nominalLembur,
                        'created_at' => now(),
                    ]
                );
            } else {
                \Illuminate\Support\Facades\DB::table('lembur')
                    ->where('pegawai_id', $dbPegId)
                    ->where(function ($q) use ($periodeLembur, $bulanNama, $tahun) {
                        $q->where('bulan', $periodeLembur)
                          ->orWhere('bulan', 'ilike', "%$bulanNama $tahun%");
                    })
                    ->update([
                        'jam_lembur' => 0,
                        'uang_lembur' => 0,
                    ]);
            }

            // 2. Sinkronkan ke tabel payroll untuk SEMUA record pegawai ini di bulan & tahun ini
            $payrolls = \Illuminate\Support\Facades\DB::table('payroll')
                ->where('pegawai_id', $dbPegId)
                ->where(function ($q) use ($bulan, $tahun, $periodeLembur) {
                    $q->where(function ($sq) use ($bulan, $tahun) {
                        $sq->where('bulan', $bulan)->where('tahun', $tahun);
                    })->orWhere('periode', $periodeLembur)
                      ->orWhere('periode', "$bulan-$tahun");
                })
                ->get();

            foreach ($payrolls as $existingPayroll) {
                $selisihLembur = $nominalLembur - (int) ($existingPayroll->lembur ?? 0);
                \Illuminate\Support\Facades\DB::table('payroll')
                    ->where('id', $existingPayroll->id)
                    ->update([
                        'lembur' => $nominalLembur,
                        'total_pendapatan' => \Illuminate\Support\Facades\DB::raw('total_pendapatan + ' . $selisihLembur),
                        'gaji_bersih' => \Illuminate\Support\Facades\DB::raw('gaji_bersih + ' . $selisihLembur),
                        'updated_at' => now(),
                    ]);
            }

            // 3. Sinkronkan juga ke tabel insentif jika ada
            \Illuminate\Support\Facades\DB::table('insentif')
                ->where('pegawai_id', $dbPegId)
                ->where(function ($q) use ($periodeLembur, $bulanNama, $tahun) {
                    $q->where('periode', $periodeLembur)
                      ->orWhere('periode', 'ilike', "%$bulanNama $tahun%")
                      ->orWhere('periode', 'ilike', "%$tahun%");
                })
                ->update([
                    'lembur' => $nominalLembur,
                ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Sync prestasi lembur failed: ' . $e->getMessage());
        }
    }

    /**
     * Unduh Template Excel / CSV Prestasi Pegawai.
     */
    public function downloadTemplate(Request $request)
    {
        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);
        $bulanNama = AbsensiController::BULAN[$bulan] ?? 'Bulan_' . $bulan;
        $tglDefault = sprintf('%04d-%02d-01', $tahun, $bulan);

        $pegawaiList = $this->pegawaiList();
        $filename = "Template_Prestasi_Pegawai_{$bulanNama}_{$tahun}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = [
            'NO', 'NIK', 'NAMA', 'TANGGAL', 'KARYA', 'ABSENSI', 'JAM LEMBUR',
            'ALPHA', 'IZIN KET', 'IZIN NON KET', 'SAKIT KET', 'SAKIT NON KET',
            'DINAS LUAR', 'CUTI', 'KETERANGAN CUTI'
        ];

        $callback = function () use ($columns, $pegawaiList, $tglDefault) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $columns);

            $no = 1;
            foreach ($pegawaiList as $p) {
                fputcsv($handle, [
                    $no++,
                    $p['nik'] ?? '',
                    $p['nama'] ?? '',
                    $tglDefault,
                    40,  // Karya %
                    60,  // Absensi %
                    0,   // Jam Lembur
                    0,   // Alpha
                    0,   // Izin Ket
                    0,   // Izin Non Ket
                    0,   // Sakit Ket
                    0,   // Sakit Non Ket
                    0,   // Dinas Luar
                    0,   // Cuti
                    '-', // Keterangan Cuti
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Impor Data Prestasi dari File Excel (.xlsx / .csv).
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
        $prefix = sprintf('%04d-%02d', $tahun, $bulan);
        $monthPadded = str_pad($bulan, 2, '0', STR_PAD_LEFT);
        $bulanNama = AbsensiController::BULAN[$bulan] ?? "Bulan {$bulan}";
        $fullBulanLabel = "{$bulanNama} {$tahun}";

        $rows = $this->readUploadedRows($request->file('file_excel'), 'prestasi');
        if (empty($rows)) {
            return back()->with('error', 'Gagal membaca isi file atau file kosong. Pastikan format sesuai template.');
        }

        $headerMap = $this->extractHeaderMap($rows, ['karya', 'absensi', 'jam_lembur', 'jam_lebur', 'alpha', 'a']);
        if (! $headerMap || ! isset($headerMap['nik'])) {
            return back()->with('error', 'Header kolom NIK tidak ditemukan dalam file. Gunakan template yang disediakan.');
        }

        $colNik = $headerMap['nik'];
        $colTanggal = $this->findHeaderCol($headerMap, ['tanggal', 'tgl_prestasi', 'tgl']);
        $colKarya = $this->findHeaderCol($headerMap, ['karya', 'nilai_karya']);
        $colAbsensi = $this->findHeaderCol($headerMap, ['absensi', 'nilai_absensi']);
        $colJamLembur = $this->findHeaderCol($headerMap, ['jam_lembur', 'jam_lebur', 'lembur', 'jml_jam_lembur']);
        $colTotalLembur = $this->findHeaderCol($headerMap, ['total_uang_lembur', 'uang_lembur', 'nominal_lembur']);
        $colAlpha = $this->findHeaderCol($headerMap, ['alpha', 'a', 'alpa']);
        $colIzinKet = $this->findHeaderCol($headerMap, ['izin_ket', 'i', 'izin_keterangan', 'izin']);
        $colIzinNonKet = $this->findHeaderCol($headerMap, ['izin_non_ket', 'itk', 'izin_tanpa_ket', 'izin_tanpa_keterangan']);
        $colSakitKet = $this->findHeaderCol($headerMap, ['sakit_ket', 's', 'sakit_keterangan', 'sakit']);
        $colSakitNonKet = $this->findHeaderCol($headerMap, ['sakit_non_ket', 'std', 'stk', 'sakit_tanpa_ket', 'sakit_tanpa_dokter']);
        $colDinasLuar = $this->findHeaderCol($headerMap, ['dinas_luar', 'dl']);
        $colCuti = $this->findHeaderCol($headerMap, ['cuti']);
        $colKetCuti = $this->findHeaderCol($headerMap, ['keterangan_cuti', 'ket_cuti', 'alasan_cuti']);

        $pegawaiDbMap = \Illuminate\Support\Facades\DB::table('pegawai')->pluck('id', 'nik')->all();

        // Ambil data prestasi yang sudah ada di bulan & tahun ini
        $existingRows = \Illuminate\Support\Facades\DB::table('prestasi')
            ->where(function ($q) use ($prefix, $tahun, $bulan, $monthPadded) {
                $q->where('tanggal', 'like', "$prefix%")
                  ->orWhere('tanggal', 'like', "%$tahun-$monthPadded-%")
                  ->orWhere('tanggal', 'like', "%$tahun-$bulan-%");
            })
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

            $tglVal = $colTanggal !== null ? trim((string)($r[$colTanggal] ?? '')) : '';
            if (empty($tglVal) || !strtotime($tglVal)) {
                $tglVal = sprintf('%04d-%02d-01', $tahun, $bulan);
            } else {
                $tglVal = date('Y-m-d', strtotime($tglVal));
            }

            $karya = $colKarya !== null ? trim((string)($r[$colKarya] ?? '40')) : '40';
            if ($karya === '') {
                $karya = '0';
            }
            $absensi = $colAbsensi !== null ? trim((string)($r[$colAbsensi] ?? '60')) : '60';
            if ($absensi === '') {
                $absensi = '0';
            }

            $rawJam = $colJamLembur !== null ? str_replace(',', '.', trim((string)($r[$colJamLembur] ?? '0'))) : '0';
            $jamLembur = (float) preg_replace('/[^0-9.]/', '', $rawJam);
            $nominalLembur = (int) round($jamLembur * self::RATE_LEMBUR_PER_JAM);
            if ($nominalLembur === 0 && $colTotalLembur !== null) {
                $rawNom = preg_replace('/[^0-9]/', '', (string) ($r[$colTotalLembur] ?? '0'));
                if ($rawNom !== '' && (int) $rawNom > 0) {
                    $nominalLembur = (int) $rawNom;
                    if ($jamLembur <= 0) {
                        $jamLembur = round($nominalLembur / self::RATE_LEMBUR_PER_JAM, 2);
                    }
                }
            }

            $alpha = $colAlpha !== null ? (int) preg_replace('/[^0-9]/', '', (string)($r[$colAlpha] ?? '0')) : 0;
            $izinKet = $colIzinKet !== null ? (int) preg_replace('/[^0-9]/', '', (string)($r[$colIzinKet] ?? '0')) : 0;
            $izinNonKet = $colIzinNonKet !== null ? (int) preg_replace('/[^0-9]/', '', (string)($r[$colIzinNonKet] ?? '0')) : 0;
            $sakitKet = $colSakitKet !== null ? (int) preg_replace('/[^0-9]/', '', (string)($r[$colSakitKet] ?? '0')) : 0;
            $sakitNonKet = $colSakitNonKet !== null ? (int) preg_replace('/[^0-9]/', '', (string)($r[$colSakitNonKet] ?? '0')) : 0;
            $dinasLuar = $colDinasLuar !== null ? (int) preg_replace('/[^0-9]/', '', (string)($r[$colDinasLuar] ?? '0')) : 0;
            $cuti = $colCuti !== null ? (int) preg_replace('/[^0-9]/', '', (string)($r[$colCuti] ?? '0')) : 0;
            $alasanCuti = $colKetCuti !== null ? trim((string)($r[$colKetCuti] ?? '-')) : '-';
            if ($alasanCuti === '' || $alasanCuti === '0') {
                $alasanCuti = '-';
            }

            $meta = [
                'desc' => "Karya: {$karya}, Absensi: {$absensi}" . ($jamLembur > 0 ? ", Lembur: {$jamLembur} jam (Rp " . number_format($nominalLembur, 0, ',', '.') . ")" : ''),
                'karya' => $karya,
                'absensi' => $absensi,
                'jam_lembur' => $jamLembur,
                'nominal_lembur' => $nominalLembur,
                'alpha' => $alpha,
                'izin_ket' => $izinKet,
                'izin_non_ket' => $izinNonKet,
                'sakit_ket' => $sakitKet,
                'sakit_non_ket' => $sakitNonKet,
                'dinas_luar' => $dinasLuar,
                'cuti' => $cuti,
                'alasan_cuti' => $alasanCuti,
            ];

            if ($existingRows->has($dbPegId)) {
                if ($mode === 'update') {
                    $existing = $existingRows->get($dbPegId);
                    \Illuminate\Support\Facades\DB::table('prestasi')
                        ->where('id', $existing->id)
                        ->update([
                            'judul'      => $karya,
                            'tanggal'    => $tglVal,
                            'keterangan' => json_encode($meta),
                            'tingkat'    => $absensi,
                        ]);
                    $updatedCount++;
                }
            } else {
                $newId = \Illuminate\Support\Facades\DB::table('prestasi')->insertGetId([
                    'pegawai_id' => $dbPegId,
                    'judul'      => $karya,
                    'tanggal'    => $tglVal,
                    'keterangan' => json_encode($meta),
                    'tingkat'    => $absensi,
                    'created_at' => $now,
                ]);
                $existingRows->put($dbPegId, (object) ['id' => $newId, 'pegawai_id' => $dbPegId]);
                $importedCount++;
            }

            // Sinkronisasi otomatis lembur ke tabel lembur & payroll
            static::syncPrestasiLemburToTables($dbPegId, $jamLembur, $nominalLembur, $tglVal);
        }

        \Illuminate\Support\Facades\Cache::forget('simpeg_all_pegawai_list');
        \Illuminate\Support\Facades\Cache::forget('pegawai_master_cache');
        \Illuminate\Support\Facades\Cache::forget('simpeg_dashboard_stats');

        return redirect()->route('prestasi.index')
            ->with('success', "Proses impor selesai! {$importedCount} data prestasi baru ditambahkan, {$updatedCount} diperbarui untuk periode {$fullBulanLabel}.");
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'pegawai_id' => 'required',
            'tanggal' => 'required|date',
            'karya' => 'required|string|max:100',
            'absensi' => 'required|string|max:100',
            'alpha' => 'required|integer|min:0|max:31',
            'izin_ket' => 'required|integer|min:0|max:31',
            'izin_non_ket' => 'required|integer|min:0|max:31',
            'sakit_ket' => 'required|integer|min:0|max:31',
            'sakit_non_ket' => 'required|integer|min:0|max:31',
            'dinas_luar' => 'required|integer|min:0|max:31',
            'cuti' => 'required|integer|min:0|max:31',
            'alasan_cuti' => 'nullable|string|max:255',
            'jam_lembur' => 'required|numeric|min:0|max:300',
        ]);
    }
}
