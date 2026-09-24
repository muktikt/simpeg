<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AbsensiController extends Controller
{
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
