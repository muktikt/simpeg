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
        if (! session()->has('dummy_absensi')) {
            session()->put('dummy_absensi', [
                ['id' => 1, 'pegawai_id' => 1, 'bulan' => 7, 'tahun' => 2026, 'hari_kerja' => 23, 'hadir' => 22, 'sakit' => 1, 'izin' => 0, 'alpha' => 0, 'keterangan' => ''],
                ['id' => 2, 'pegawai_id' => 2, 'bulan' => 7, 'tahun' => 2026, 'hari_kerja' => 23, 'hadir' => 20, 'sakit' => 0, 'izin' => 2, 'alpha' => 1, 'keterangan' => 'Izin keperluan keluarga'],
                ['id' => 3, 'pegawai_id' => 3, 'bulan' => 7, 'tahun' => 2026, 'hari_kerja' => 23, 'hadir' => 23, 'sakit' => 0, 'izin' => 0, 'alpha' => 0, 'keterangan' => ''],
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

    /**
     * SET Hari Kerja - disamakan dengan sistem lama (set_hari_kerja.php).
     * Ternyata cuma SATU nilai global (tbl_hari_kerja, tanpa filter id di
     * query UPDATE-nya - bukan daftar per bulan/tahun, cuma 1 angka
     * "Hari Kerja dalam 1 Bulan" yang dipakai di seluruh sistem). Karena
     * itu di sini tidak dibuat CRUD, cukup 1 halaman edit nilai tunggal.
     */
    public function hariKerjaEdit()
    {
        $hariKerja = session('dummy_hari_kerja', 25);

        return view('absensi.hari-kerja', compact('hariKerja'));
    }

    public function hariKerjaUpdate(Request $request)
    {
        $validated = $request->validate([
            'hari_kerja' => 'required|integer|min:1|max:31',
        ]);

        session()->put('dummy_hari_kerja', $validated['hari_kerja']);

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
