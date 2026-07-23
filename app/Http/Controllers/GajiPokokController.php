<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GajiPokokController extends Controller
{
    /**
     * DATA DUMMY BERBASIS SESSION.
     *
     * Struktur ini disamakan dengan sistem lama (set_gaji_pokok.php):
     *   tbl_gapok (id_gapok, id_golongan, tahun_golongan, nominal_gapok)
     *   INNER JOIN tbl_golongan (id_golongan, kode_golongan)
     *
     * Jadi satu Golongan bisa punya beberapa baris gaji pokok, tergantung
     * masa kerja (tahun_golongan) - bukan 1 golongan = 1 nominal tetap.
     * Sistem lama TIDAK punya fitur hapus (cuma tambah + edit), jadi di
     * sini juga sengaja tidak ada tombol hapus, mengikuti aslinya.
     */
    protected function seedIfEmpty(): void
    {
        if (! session()->has('dummy_gapok')) {
            session()->put('dummy_gapok', [
                ['id' => 1, 'golongan' => 'II/C', 'masa_kerja' => '0-5 tahun', 'nominal' => 2900000],
                ['id' => 2, 'golongan' => 'II/D', 'masa_kerja' => '0-5 tahun', 'nominal' => 3200000],
                ['id' => 3, 'golongan' => 'II/D', 'masa_kerja' => '6-10 tahun', 'nominal' => 3600000],
                ['id' => 4, 'golongan' => 'III/A', 'masa_kerja' => '0-5 tahun', 'nominal' => 3800000],
                ['id' => 5, 'golongan' => 'III/A', 'masa_kerja' => '6-10 tahun', 'nominal' => 4300000],
                ['id' => 6, 'golongan' => 'III/B', 'masa_kerja' => '0-5 tahun', 'nominal' => 4100000],
                ['id' => 7, 'golongan' => 'III/B', 'masa_kerja' => '6-10 tahun', 'nominal' => 4600000],
                ['id' => 8, 'golongan' => 'IV/A', 'masa_kerja' => '0-5 tahun', 'nominal' => 5200000],
                ['id' => 9, 'golongan' => 'IV/A', 'masa_kerja' => '6-10 tahun', 'nominal' => 5800000],
                ['id' => 10, 'golongan' => 'IV/B', 'masa_kerja' => '0-5 tahun', 'nominal' => 6500000],
            ]);
        }
    }

    protected function all(): array
    {
        $this->seedIfEmpty();

        return session('dummy_gapok', []);
    }

    protected function save(array $data): void
    {
        session()->put('dummy_gapok', $data);
    }

    public function index()
    {
        $gapok = collect($this->all())
            ->sortBy(['golongan', 'masa_kerja'])
            ->values();

        return view('gaji-pokok.index', compact('gapok'));
    }

    public function create()
    {
        return view('gaji-pokok.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        $data = $this->all();
        $newId = $data ? max(array_column($data, 'id')) + 1 : 1;
        $validated['id'] = $newId;

        $data[] = $validated;
        $this->save($data);

        return redirect()->route('gaji-pokok.index')->with('success', 'Data gaji pokok berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $gapok = collect($this->all())->firstWhere('id', $id);
        abort_if(! $gapok, 404);

        return view('gaji-pokok.edit', compact('gapok'));
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

        return redirect()->route('gaji-pokok.index')->with('success', 'Data gaji pokok berhasil diperbarui.');
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'golongan' => 'required|string|max:20',
            'masa_kerja' => 'required|string|max:50',
            'nominal' => 'required|numeric|min:0',
        ]);
    }
}
