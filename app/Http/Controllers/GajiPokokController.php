<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GajiPokokController extends Controller
{
    /**
     * Modul Pengelolaan Gaji Pokok.
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
    protected function storageFile(): string
    {
        return storage_path('app/gaji_pokok.json');
    }

    protected function all(): array
    {
        $file = $this->storageFile();
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if (is_array($data)) {
                return $data;
            }
        }
        return [];
    }

    protected function save(array $data): void
    {
        $file = $this->storageFile();
        @file_put_contents($file, json_encode(array_values($data), JSON_PRETTY_PRINT));
    }

    public function index()
    {
        $gapok = collect($this->all())
            ->sortBy(['golongan', 'masa_kerja'])
            ->values();

        return view('gaji-pokok.index', compact('gapok'));
    }

    /**
     * Halaman Laporan (read-only, format cetak) - dipisah dari index()
     * yang jadi halaman kelola/SET. Data sumbernya sama, tampilannya beda.
     */
    public function laporan()
    {
        $gapok = collect($this->all())
            ->sortBy(['golongan', 'masa_kerja'])
            ->values();

        return view('gaji-pokok.laporan', compact('gapok'));
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
