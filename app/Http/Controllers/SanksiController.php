<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SanksiController extends Controller
{
    /**
     * DATA DUMMY BERBASIS SESSION.
     *
     * Struktur disamakan dengan sistem lama (set_sanksi_pegawai.php / tambah_sanksi_pegawai.php):
     *   tbl_sanksi (id, id_pegawai, nik, tgl_sanksi, jenis_sanksi, ket_sanksi, pot_persen)
     *
     * CATATAN PERBAIKAN: di form tambah/edit sanksi versi lama, ketiga pilihan jenis
     * sanksi (Lisan/Tulisan/Dikeluarkan) semuanya punya value="Lisan" (bug copy-paste),
     * jadi apapun yang dipilih selalu tersimpan sebagai "Lisan". Di versi ini sudah
     * diperbaiki supaya value-nya sesuai pilihan yang benar-benar dipilih.
     *
     * Aturan sistem lama: satu pegawai tidak boleh punya lebih dari satu sanksi
     * di bulan & tahun yang sama - aturan ini dipertahankan di validateData().
     */
    public const JENIS_SANKSI = ['Lisan', 'Tulisan', 'Dikeluarkan'];

    protected function seedIfEmpty(): void
    {
        if (! session()->has('dummy_sanksi')) {
            session()->put('dummy_sanksi', [
                ['id' => 1, 'pegawai_id' => 2, 'tanggal' => '2026-06-10', 'jenis_sanksi' => 'Lisan', 'keterangan' => 'Terlambat masuk kerja berulang kali', 'potongan_persen' => 5],
            ]);
        }
    }

    protected function all(): array
    {
        $this->seedIfEmpty();

        return session('dummy_sanksi', []);
    }

    protected function save(array $data): void
    {
        session()->put('dummy_sanksi', $data);
    }

    protected function pegawaiList(): array
    {
        return session('dummy_pegawai', []);
    }

    protected function pegawaiById(int $id): ?array
    {
        return collect($this->pegawaiList())->firstWhere('id', $id);
    }

    protected function withPegawai(array $rows): array
    {
        return collect($rows)->map(function ($row) {
            $p = $this->pegawaiById($row['pegawai_id']);
            $row['nik'] = $p['nik'] ?? '-';
            $row['nama'] = $p['nama'] ?? '(pegawai tidak ditemukan)';

            return $row;
        })->all();
    }

    public function index()
    {
        $sanksi = collect($this->withPegawai($this->all()))
            ->sortByDesc('tanggal')
            ->values();

        return view('sanksi.index', compact('sanksi'));
    }

    public function create()
    {
        return view('sanksi.create', [
            'pegawaiList' => $this->pegawaiList(),
            'jenisSanksiList' => self::JENIS_SANKSI,
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

        return redirect()->route('sanksi.index')->with('success', 'Data sanksi berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $sanksi = collect($this->all())->firstWhere('id', $id);
        abort_if(! $sanksi, 404);

        return view('sanksi.edit', [
            'sanksi' => $sanksi,
            'pegawaiList' => $this->pegawaiList(),
            'jenisSanksiList' => self::JENIS_SANKSI,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $validated = $this->validateData($request, $id);

        $data = collect($this->all())->map(function ($row) use ($id, $validated) {
            if ($row['id'] === $id) {
                $validated['id'] = $id;

                return $validated;
            }

            return $row;
        })->all();

        $this->save($data);

        return redirect()->route('sanksi.index')->with('success', 'Data sanksi berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $data = collect($this->all())->reject(fn ($row) => $row['id'] === $id)->values()->all();
        $this->save($data);

        return redirect()->route('sanksi.index')->with('success', 'Data sanksi berhasil dihapus.');
    }

    protected function validateData(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'pegawai_id' => 'required|integer',
            'tanggal' => 'required|date',
            'jenis_sanksi' => ['required', Rule::in(self::JENIS_SANKSI)],
            'keterangan' => 'nullable|string|max:255',
            'potongan_persen' => 'required|numeric|min:0|max:100',
        ]);

        // Aturan sistem lama: 1 pegawai cuma boleh 1 sanksi per bulan+tahun.
        $bulan = \Illuminate\Support\Carbon::parse($validated['tanggal'])->month;
        $tahun = \Illuminate\Support\Carbon::parse($validated['tanggal'])->year;

        $sudahAda = collect($this->all())
            ->reject(fn ($row) => $row['id'] === $ignoreId)
            ->contains(function ($row) use ($validated, $bulan, $tahun) {
                $rowDate = \Illuminate\Support\Carbon::parse($row['tanggal']);

                return $row['pegawai_id'] == $validated['pegawai_id']
                    && $rowDate->month === $bulan
                    && $rowDate->year === $tahun;
            });

        if ($sudahAda) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'pegawai_id' => 'Pegawai ini sudah punya data sanksi di bulan & tahun yang sama.',
            ]);
        }

        return $validated;
    }
}
