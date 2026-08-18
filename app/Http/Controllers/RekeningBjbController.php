<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RekeningBjbController extends Controller
{
    /**
     * DATA DUMMY BERBASIS SESSION.
     *
     * Disamakan dengan sistem lama (set_rekening_bjbs.php):
     *   tbl_rek_bjbs (nik, no_rek)
     */
    protected function seedIfEmpty(): void
    {
        if (! session()->has('dummy_rek_bjb')) {
            $pegawai = collect(session('dummy_pegawai', []))->where('status_peg', '!=', 'PN')->take(3);
            $seed = [];
            foreach ($pegawai as $idx => $p) {
                $seed[] = [
                    'id'     => $idx + 1,
                    'nik'    => $p['nik'],
                    'no_rek' => '00' . rand(10000000, 99999999),
                ];
            }
            session()->put('dummy_rek_bjb', $seed);
        }
    }

    protected function all(): array
    {
        $this->seedIfEmpty();
        return session('dummy_rek_bjb', []);
    }

    protected function save(array $data): void
    {
        session()->put('dummy_rek_bjb', $data);
    }

    protected function pegawaiList(): array
    {
        return collect(app(PegawaiController::class)->all())->where('status_peg', '!=', 'PN')->values()->all();
    }

    protected function withNama(array $row): array
    {
        $p = collect($this->pegawaiList())->firstWhere('nik', $row['nik']);
        $row['nama'] = $p['nama'] ?? '(tidak ditemukan)';
        return $row;
    }

    public function index()
    {
        $items = collect($this->all())
            ->map(fn ($r) => $this->withNama($r))
            ->sortBy('nik')
            ->values();

        $totalPegawai = count($this->pegawaiList());
        $sudahMasuk   = $items->count();
        $belumMasuk   = max(0, $totalPegawai - $sudahMasuk);

        return view('rekening-bjb.index', compact('items', 'belumMasuk'));
    }

    public function create()
    {
        return view('rekening-bjb.create', ['pegawaiList' => $this->pegawaiList()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pegawai_id' => 'required|integer',
            'no_rek'     => 'required|string|max:30',
        ]);

        $peg = collect($this->pegawaiList())->firstWhere('id', (int) $validated['pegawai_id']);
        abort_if(! $peg, 404);

        $data = $this->all();
        $newId = $data ? max(array_column($data, 'id')) + 1 : 1;
        $data[] = [
            'id'     => $newId,
            'nik'    => $peg['nik'],
            'no_rek' => $validated['no_rek'],
        ];
        $this->save($data);

        return redirect()->route('rekening-bjb.index')->with('success', 'Rekening BJB berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $item = collect($this->all())->firstWhere('id', $id);
        abort_if(! $item, 404);
        return view('rekening-bjb.edit', ['item' => $item, 'pegawaiList' => $this->pegawaiList()]);
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'pegawai_id' => 'required|integer',
            'no_rek'     => 'required|string|max:30',
        ]);

        $peg = collect($this->pegawaiList())->firstWhere('id', (int) $validated['pegawai_id']);
        abort_if(! $peg, 404);

        $data = collect($this->all())->map(function ($row) use ($id, $peg, $validated) {
            if ($row['id'] === $id) {
                $row['nik']    = $peg['nik'];
                $row['no_rek'] = $validated['no_rek'];
            }
            return $row;
        })->all();

        $this->save($data);
        return redirect()->route('rekening-bjb.index')->with('success', 'Rekening BJB berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $data = collect($this->all())->reject(fn ($r) => $r['id'] === $id)->values()->all();
        $this->save($data);
        return redirect()->route('rekening-bjb.index')->with('success', 'Rekening BJB berhasil dihapus.');
    }

    public function belumMasuk()
    {
        $nikSudah = collect($this->all())->pluck('nik')->unique()->all();
        $belum = collect($this->pegawaiList())->reject(fn ($p) => in_array($p['nik'], $nikSudah));
        return view('rekening-bjb.belum-masuk', ['pegawai' => $belum->values()]);
    }
}
