<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DrdTukinController extends Controller
{
    /**
     * DATA DUMMY BERBASIS SESSION.
     *
     * Struktur disamakan dengan sistem lama (set_drd_tukin.php / tambah_drd_tukin.php):
     *   tbl_drd (id_efisiensi, tgl_efisiensi, drd, penerimaan, drd_persen, nik)
     *
     * "drd" = nominal anggaran DRD, "penerimaan" = nominal realisasi penerimaan.
     * "drd_persen" (Efisiensi %) DIHITUNG OTOMATIS dari (penerimaan / drd) * 100,
     * persis seperti logic di tambah_drd_tukin.php - bukan input manual.
     */
    protected function seedIfEmpty(): void
    {
        if (! session()->has('dummy_drd')) {
            session()->put('dummy_drd', [
                ['id' => 1, 'tanggal' => '2026-05-01', 'nominal_drd' => 50000000, 'nominal_penerimaan' => 47500000],
                ['id' => 2, 'tanggal' => '2026-06-01', 'nominal_drd' => 50000000, 'nominal_penerimaan' => 49000000],
            ]);
        }
    }

    protected function all(): array
    {
        $this->seedIfEmpty();

        return session('dummy_drd', []);
    }

    protected function save(array $data): void
    {
        session()->put('dummy_drd', $data);
    }

    protected function withPersen(array $row): array
    {
        $row['efisiensi_persen'] = $row['nominal_drd'] > 0
            ? round(($row['nominal_penerimaan'] / $row['nominal_drd']) * 100, 2)
            : 0;

        return $row;
    }

    public function index()
    {
        $drd = collect($this->all())
            ->map(fn ($row) => $this->withPersen($row))
            ->sortByDesc('tanggal')
            ->values();

        return view('drd-tukin.index', compact('drd'));
    }

    public function create()
    {
        return view('drd-tukin.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        $data = $this->all();
        $newId = $data ? max(array_column($data, 'id')) + 1 : 1;
        $validated['id'] = $newId;

        $data[] = $validated;
        $this->save($data);

        return redirect()->route('drd-tukin.index')->with('success', 'Data DRD Tukin berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $drd = collect($this->all())->firstWhere('id', $id);
        abort_if(! $drd, 404);

        return view('drd-tukin.edit', compact('drd'));
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

        return redirect()->route('drd-tukin.index')->with('success', 'Data DRD Tukin berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $data = collect($this->all())->reject(fn ($row) => $row['id'] === $id)->values()->all();
        $this->save($data);

        return redirect()->route('drd-tukin.index')->with('success', 'Data DRD Tukin berhasil dihapus.');
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'tanggal' => 'required|date',
            'nominal_drd' => 'required|numeric|min:1',
            'nominal_penerimaan' => 'required|numeric|min:0',
        ]);
    }
}
