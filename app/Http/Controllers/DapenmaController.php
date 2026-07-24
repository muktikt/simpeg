<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DapenmaController extends Controller
{
    /**
     * DATA DUMMY BERBASIS SESSION.
     *
     * Disamakan dengan sistem lama (set_phdp_dapenma.php / tambah_peserta_dapenma.php):
     *   tbl_dapenma (id, nik, nomor_peserta, nominal_phdp, nominal_beban,
     *                petugas_entri, tgl_update)
     *
     * Formula ditemukan di kode asli: Nominal Beban = 5% x Nominal PHDP,
     * dihitung otomatis (bukan input manual).
     */
    protected function seedIfEmpty(): void
    {
        if (! session()->has('dummy_dapenma') || count(session('dummy_dapenma', [])) <= 1) {
            session()->put('dummy_dapenma', [
                ['id' => 1, 'pegawai_id' => 1, 'nomor_peserta' => 'DPM-2017-001', 'nominal_phdp' => 4200000, 'tgl_update' => '2026-07-24'],
                ['id' => 2, 'pegawai_id' => 2, 'nomor_peserta' => 'DPM-2018-002', 'nominal_phdp' => 3800000, 'tgl_update' => '2026-07-24'],
                ['id' => 3, 'pegawai_id' => 3, 'nomor_peserta' => 'DPM-2022-003', 'nominal_phdp' => 3200000, 'tgl_update' => '2026-07-24'],
            ]);
        }
    }

    protected function all(): array
    {
        $this->seedIfEmpty();

        return session('dummy_dapenma', []);
    }

    protected function save(array $data): void
    {
        session()->put('dummy_dapenma', $data);
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
        $row['nominal_beban'] = $row['nominal_phdp'] * 0.05;
        $row['tgl_update'] = $row['tgl_update'] ?? now()->toDateString();

        return $row;
    }

    public function index()
    {
        $dapenma = collect($this->all())
            ->map(fn ($row) => $this->withCalculated($row))
            ->sortBy('nama')
            ->values();

        return view('dapenma.index', compact('dapenma'));
    }

    public function create()
    {
        return view('dapenma.create', ['pegawaiList' => $this->pegawaiList()]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);
        $validated['petugas_entri'] = session('simpeg_user.nama_peg', 'Admin');
        $validated['tgl_update'] = now()->toDateString();

        $data = $this->all();
        $newId = $data ? max(array_column($data, 'id')) + 1 : 1;
        $validated['id'] = $newId;

        $data[] = $validated;
        $this->save($data);

        return redirect()->route('dapenma.index')->with('success', 'Data peserta Dapenma berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $dapenma = collect($this->all())->firstWhere('id', $id);
        abort_if(! $dapenma, 404);

        return view('dapenma.edit', [
            'dapenma' => $dapenma,
            'pegawaiList' => $this->pegawaiList(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $validated = $this->validateData($request);
        $validated['petugas_entri'] = session('simpeg_user.nama_peg', 'Admin');
        $validated['tgl_update'] = now()->toDateString();

        $data = collect($this->all())->map(function ($row) use ($id, $validated) {
            if ($row['id'] === $id) {
                $validated['id'] = $id;

                return $validated;
            }

            return $row;
        })->all();

        $this->save($data);

        return redirect()->route('dapenma.index')->with('success', 'Data peserta Dapenma berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $data = collect($this->all())->reject(fn ($row) => $row['id'] === $id)->values()->all();
        $this->save($data);

        return redirect()->route('dapenma.index')->with('success', 'Data peserta Dapenma berhasil dihapus.');
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'pegawai_id' => 'required|integer',
            'nomor_peserta' => 'required|string|max:50',
            'nominal_phdp' => 'required|numeric|min:0',
        ]);
    }
}
