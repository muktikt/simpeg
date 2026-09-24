<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PrestasiController extends Controller
{
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

        // 2. Sinkron ke tabel lembur jika jam_lembur > 0
        if ($jamLembur > 0) {
            try {
                $tglCarbon = \Illuminate\Support\Carbon::parse($validated['tanggal']);
                $bulanNama = AbsensiController::BULAN[$tglCarbon->month] ?? $tglCarbon->translatedFormat('F');
                $periodeLembur = "{$bulanNama} {$tglCarbon->year}";

                \Illuminate\Support\Facades\DB::table('lembur')->updateOrInsert(
                    [
                        'pegawai_id' => $dbPegId,
                        'bulan' => $periodeLembur,
                    ],
                    [
                        'jam_lembur' => (int) round($jamLembur),
                        'uang_lembur' => $nominalLembur,
                        'created_at' => now(),
                    ]
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Sync prestasi lembur to lembur table failed: ' . $e->getMessage());
            }
        }

        // 3. Clear cache pegawai
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

        if ($dbPegId && $jamLembur > 0) {
            try {
                $tglCarbon = \Illuminate\Support\Carbon::parse($validated['tanggal']);
                $bulanNama = AbsensiController::BULAN[$tglCarbon->month] ?? $tglCarbon->translatedFormat('F');
                $periodeLembur = "{$bulanNama} {$tglCarbon->year}";

                \Illuminate\Support\Facades\DB::table('lembur')->updateOrInsert(
                    [
                        'pegawai_id' => $dbPegId,
                        'bulan' => $periodeLembur,
                    ],
                    [
                        'jam_lembur' => (int) round($jamLembur),
                        'uang_lembur' => $nominalLembur,
                        'created_at' => now(),
                    ]
                );
            } catch (\Throwable $e) {}
        }

        \Illuminate\Support\Facades\Cache::forget('simpeg_all_pegawai_list');
        \Illuminate\Support\Facades\Cache::forget('pegawai_master_cache');
        \Illuminate\Support\Facades\Cache::forget('simpeg_dashboard_stats');

        return redirect()->route('prestasi.index')->with('success', 'Data prestasi pegawai berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        try {
            \Illuminate\Support\Facades\DB::table('prestasi')->where('id', $id)->delete();
        } catch (\Throwable $e) {}

        \Illuminate\Support\Facades\Cache::forget('simpeg_all_pegawai_list');
        \Illuminate\Support\Facades\Cache::forget('pegawai_master_cache');
        \Illuminate\Support\Facades\Cache::forget('simpeg_dashboard_stats');

        return redirect()->route('prestasi.index')->with('success', 'Data prestasi berhasil dihapus.');
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'pegawai_id' => 'required|integer',
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
