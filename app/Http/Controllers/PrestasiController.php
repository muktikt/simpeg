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
            session()->put('dummy_prestasi_gaji', [
                [
                    'id' => 1, 'pegawai_id' => 1, 'tanggal' => '2026-06-01', 'karya' => 'Baik',
                    'absensi' => 'Baik', 'alpha' => 0, 'izin_ket' => 0, 'izin_non_ket' => 0,
                    'sakit_ket' => 1, 'sakit_non_ket' => 0, 'dinas_luar' => 2, 'cuti' => 0,
                    'alasan_cuti' => '', 'jam_lembur' => 8,
                ],
            ]);
        }
    }

    protected function all(): array
    {
        $this->seedIfEmpty();

        return session('dummy_prestasi_gaji', []);
    }

    protected function save(array $data): void
    {
        session()->put('dummy_prestasi_gaji', $data);
    }

    protected function pegawaiList(): array
    {
        return session('dummy_pegawai', []);
    }

    protected function pegawaiById(int $id): ?array
    {
        return collect($this->pegawaiList())->firstWhere('id', $id);
    }

    protected function withCalculated(array $row): array
    {
        $p = $this->pegawaiById($row['pegawai_id']);
        $row['nik'] = $p['nik'] ?? '-';
        $row['nama'] = $p['nama'] ?? '(pegawai tidak ditemukan)';
        $row['nominal_lembur_harian'] = self::RATE_LEMBUR_PER_JAM;
        $row['nominal_lembur'] = $row['jam_lembur'] * self::RATE_LEMBUR_PER_JAM;

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

        return redirect()->route('prestasi.index')->with('success', 'Data prestasi berhasil ditambahkan.');
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
