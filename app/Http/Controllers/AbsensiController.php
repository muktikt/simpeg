<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AbsensiController extends Controller
{
    /**
     * DATA DUMMY BERBASIS SESSION - sama seperti PegawaiController.
     * Ganti pakai Eloquent Model (tbl_absensi) kalau sudah siap ke database asli.
     *
     * Data pegawai ditarik dari session 'dummy_pegawai' (modul Data Pegawai)
     * supaya nyambung - satu sumber data yang sama, bukan data pegawai sendiri lagi.
     */
    public const BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    protected function seedIfEmpty(): void
    {
        // Pastikan data pegawai juga ada di session, supaya nama/NIK bisa tampil
        // tanpa harus buka halaman Pegawai dulu.
        if (! session()->has('dummy_pegawai')) {
            app(\App\Http\Controllers\PegawaiController::class)->seedIfEmpty();
        }

        if (! session()->has('dummy_absensi')) {
            session()->put('dummy_absensi', [
                // === Juli 2026 ===
                ['id' => 1,  'pegawai_id' => 1, 'bulan' => 7, 'tahun' => 2026, 'hari_kerja' => 23, 'hadir' => 22, 'sakit' => 1, 'izin' => 0, 'alpha' => 0, 'keterangan' => ''],
                ['id' => 2,  'pegawai_id' => 2, 'bulan' => 7, 'tahun' => 2026, 'hari_kerja' => 23, 'hadir' => 20, 'sakit' => 0, 'izin' => 2, 'alpha' => 1, 'keterangan' => 'Izin keperluan keluarga'],
                ['id' => 3,  'pegawai_id' => 3, 'bulan' => 7, 'tahun' => 2026, 'hari_kerja' => 23, 'hadir' => 23, 'sakit' => 0, 'izin' => 0, 'alpha' => 0, 'keterangan' => ''],
                ['id' => 13, 'pegawai_id' => 4, 'bulan' => 7, 'tahun' => 2026, 'hari_kerja' => 23, 'hadir' => 23, 'sakit' => 0, 'izin' => 0, 'alpha' => 0, 'keterangan' => ''],
                ['id' => 14, 'pegawai_id' => 5, 'bulan' => 7, 'tahun' => 2026, 'hari_kerja' => 23, 'hadir' => 21, 'sakit' => 1, 'izin' => 1, 'alpha' => 0, 'keterangan' => 'Izin urusan keluarga'],

                // === Juni 2026 ===
                ['id' => 4,  'pegawai_id' => 1, 'bulan' => 6, 'tahun' => 2026, 'hari_kerja' => 22, 'hadir' => 21, 'sakit' => 0, 'izin' => 1, 'alpha' => 0, 'keterangan' => 'Izin acara pernikahan'],
                ['id' => 5,  'pegawai_id' => 2, 'bulan' => 6, 'tahun' => 2026, 'hari_kerja' => 22, 'hadir' => 22, 'sakit' => 0, 'izin' => 0, 'alpha' => 0, 'keterangan' => ''],
                ['id' => 6,  'pegawai_id' => 3, 'bulan' => 6, 'tahun' => 2026, 'hari_kerja' => 22, 'hadir' => 19, 'sakit' => 2, 'izin' => 0, 'alpha' => 1, 'keterangan' => 'Sakit demam'],
                ['id' => 15, 'pegawai_id' => 4, 'bulan' => 6, 'tahun' => 2026, 'hari_kerja' => 22, 'hadir' => 22, 'sakit' => 0, 'izin' => 0, 'alpha' => 0, 'keterangan' => ''],
                ['id' => 16, 'pegawai_id' => 5, 'bulan' => 6, 'tahun' => 2026, 'hari_kerja' => 22, 'hadir' => 20, 'sakit' => 0, 'izin' => 2, 'alpha' => 0, 'keterangan' => 'Izin pribadi'],

                // === Mei 2026 ===
                ['id' => 7,  'pegawai_id' => 1, 'bulan' => 5, 'tahun' => 2026, 'hari_kerja' => 21, 'hadir' => 21, 'sakit' => 0, 'izin' => 0, 'alpha' => 0, 'keterangan' => ''],
                ['id' => 8,  'pegawai_id' => 2, 'bulan' => 5, 'tahun' => 2026, 'hari_kerja' => 21, 'hadir' => 18, 'sakit' => 1, 'izin' => 1, 'alpha' => 1, 'keterangan' => 'Izin urusan pribadi'],
                ['id' => 9,  'pegawai_id' => 3, 'bulan' => 5, 'tahun' => 2026, 'hari_kerja' => 21, 'hadir' => 20, 'sakit' => 1, 'izin' => 0, 'alpha' => 0, 'keterangan' => ''],
                ['id' => 17, 'pegawai_id' => 4, 'bulan' => 5, 'tahun' => 2026, 'hari_kerja' => 21, 'hadir' => 20, 'sakit' => 0, 'izin' => 1, 'alpha' => 0, 'keterangan' => 'Dinas luar kota'],
                ['id' => 18, 'pegawai_id' => 5, 'bulan' => 5, 'tahun' => 2026, 'hari_kerja' => 21, 'hadir' => 21, 'sakit' => 0, 'izin' => 0, 'alpha' => 0, 'keterangan' => ''],

                // === April 2026 ===
                ['id' => 10, 'pegawai_id' => 1, 'bulan' => 4, 'tahun' => 2026, 'hari_kerja' => 22, 'hadir' => 20, 'sakit' => 1, 'izin' => 1, 'alpha' => 0, 'keterangan' => ''],
                ['id' => 11, 'pegawai_id' => 2, 'bulan' => 4, 'tahun' => 2026, 'hari_kerja' => 22, 'hadir' => 22, 'sakit' => 0, 'izin' => 0, 'alpha' => 0, 'keterangan' => ''],
                ['id' => 12, 'pegawai_id' => 3, 'bulan' => 4, 'tahun' => 2026, 'hari_kerja' => 22, 'hadir' => 21, 'sakit' => 0, 'izin' => 1, 'alpha' => 0, 'keterangan' => 'Izin anak sakit'],
                ['id' => 19, 'pegawai_id' => 4, 'bulan' => 4, 'tahun' => 2026, 'hari_kerja' => 22, 'hadir' => 21, 'sakit' => 1, 'izin' => 0, 'alpha' => 0, 'keterangan' => ''],
                ['id' => 20, 'pegawai_id' => 5, 'bulan' => 4, 'tahun' => 2026, 'hari_kerja' => 22, 'hadir' => 19, 'sakit' => 0, 'izin' => 1, 'alpha' => 2, 'keterangan' => ''],
            ]);
        }
    }

    protected function all(): array
    {
        $this->seedIfEmpty();

        return session('dummy_absensi', []);
    }

    protected function save(array $data): void
    {
        session()->put('dummy_absensi', $data);
    }

    protected function pegawaiList(): array
    {
        return session('dummy_pegawai', []);
    }

    protected function pegawaiById(int $id): ?array
    {
        return collect($this->pegawaiList())->firstWhere('id', $id);
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

    public function create()
    {
        return view('absensi.create', [
            'pegawaiList' => $this->pegawaiList(),
            'bulanList' => self::BULAN,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        $data = $this->all();
        $newId = $data ? max(array_column($data, 'id')) + 1 : 1;
        $validated['id'] = $newId;

        $data[] = $validated;
        $this->save($data);

        return redirect()->route('absensi.index', ['bulan' => $validated['bulan'], 'tahun' => $validated['tahun']])
            ->with('success', 'Data absensi berhasil ditambahkan.');
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

        $data = collect($this->all())->map(function ($row) use ($id, $validated) {
            if ($row['id'] === $id) {
                $validated['id'] = $id;

                return $validated;
            }

            return $row;
        })->all();

        $this->save($data);

        return redirect()->route('absensi.index', ['bulan' => $validated['bulan'], 'tahun' => $validated['tahun']])
            ->with('success', 'Data absensi berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $data = collect($this->all())->reject(fn ($row) => $row['id'] === $id)->values()->all();
        $this->save($data);

        return redirect()->route('absensi.index')->with('success', 'Data absensi berhasil dihapus.');
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
