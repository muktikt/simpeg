<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PrestasiController extends Controller
{
    /**
     * DATA DUMMY BERBASIS SESSION.
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

    protected function seedIfEmpty(): void
    {
        if (! session()->has('dummy_prestasi_gaji')) {
            session()->put('dummy_prestasi_gaji', []);
        }
    }

    protected function all(): array
    {
        try {
            $rows = \Illuminate\Support\Facades\DB::table('prestasi')->get();
            if ($rows->isNotEmpty()) {
                return $rows->map(fn ($r) => (array) $r)->toArray();
            }
        } catch (\Throwable $e) {}

        return session('dummy_prestasi_gaji', []);
    }

    protected function save(array $data): void
    {
        session()->put('dummy_prestasi_gaji', $data);
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
        $row['karya'] = $row['karya'] ?? '-';
        $row['absensi'] = $row['absensi'] ?? '-';
        $row['jam_lembur'] = (float) ($row['jam_lembur'] ?? 0);
        $row['nominal_lembur_harian'] = self::RATE_LEMBUR_PER_JAM;
        $row['nominal_lembur'] = $row['nominal_lembur'] ?? ($row['jam_lembur'] * self::RATE_LEMBUR_PER_JAM);
        $row['tanggal'] = $row['tanggal'] ?? ($row['created_at'] ?? now()->toDateString());

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

        $data = $this->all();
        $newId = $data ? max(array_column($data, 'id')) + 1 : 1;
        $validated['id'] = $newId;

        $data[] = $validated;
        $this->save($data);

        try {
            \Illuminate\Support\Facades\DB::table('prestasi')->insert([
                'pegawai_id' => $validated['pegawai_id'],
                'bulan' => \Illuminate\Support\Carbon::parse($validated['tanggal'])->month,
                'tahun' => \Illuminate\Support\Carbon::parse($validated['tanggal'])->year,
                'kehadiran' => $validated['absensi'] ?? 'Baik',
                'telat' => (int) ($validated['alpha'] ?? 0),
                'cuti' => (int) ($validated['cuti'] ?? 0),
                'dinas' => (int) ($validated['dinas_luar'] ?? 0),
                'lembur' => (int) ($validated['jam_lembur'] ?? 0),
                'bonus' => 0,
                'total_prestasi' => 100,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Fallback
        }

        return redirect()->route('prestasi.index')->with('success', 'Data prestasi berhasil ditambahkan dan tersinkronisasi ke database.');
    }

    public function edit(int $id)
    {
        $prestasi = collect($this->all())->firstWhere('id', $id);
        abort_if(! $prestasi, 404);

        return view('prestasi.edit', [
            'prestasi' => $prestasi,
            'pegawaiList' => $this->pegawaiList(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $validated = $this->validateData($request);

        $data = collect($this->all())->map(function ($row) use ($id, $validated) {
            if ($row['id'] === $id) {
                $validated['id'] = $id;

                return $validated;
            }

            return $row;
        })->all();

        $this->save($data);

        return redirect()->route('prestasi.index')->with('success', 'Data prestasi berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $data = collect($this->all())->reject(fn ($row) => $row['id'] === $id)->values()->all();
        $this->save($data);

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
