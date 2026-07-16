<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PegawaiController extends Controller
{
    /**
     * DATA DUMMY BERBASIS SESSION.
     *
     * Ini BUKAN koneksi database - data disimpan di session browser supaya
     * fitur tambah/edit/hapus beneran bisa dicoba tanpa perlu setup DB dulu.
     * Data bakal hilang kalau session di-clear / ganti browser.
     *
     * Ganti seluruh method di controller ini pakai Eloquent Model (mis. Pegawai::all())
     * kalau sudah siap dihubungkan ke tabel tbl_pegawai yang asli.
     */
    protected function seedIfEmpty(): void
    {
        if (! session()->has('dummy_pegawai')) {
            session()->put('dummy_pegawai', [
                [
                    'id' => 1,
                    'nik' => '1711254',
                    'nama' => 'Mukti Kurniawan',
                    'jabatan' => 'Staf SDM',
                    'unit_kerja' => 'Kantor Pusat',
                    'status_peg' => 'PT',
                    'tgl_masuk' => '2017-11-25',
                    'telp' => '081234567890',
                    'alamat' => 'Jl. Merdeka No. 10, Kota A',
                    'keluarga' => [
                        ['id' => 1, 'nama' => 'Sri Wahyuni', 'hubungan' => 'Istri/Suami', 'tgl_lahir' => '1992-03-14'],
                    ],
                    'golongan' => [
                        ['id' => 1, 'golongan' => 'III/A', 'tmt' => '2020-01-01'],
                    ],
                    'jabatan_riwayat' => [
                        ['id' => 1, 'jabatan' => 'Staf SDM', 'unit_kerja' => 'Kantor Pusat', 'tmt' => '2021-06-01'],
                    ],
                    'pendidikan' => [
                        ['id' => 1, 'jenjang' => 'S1', 'jurusan' => 'Manajemen', 'institusi' => 'Universitas A', 'tahun_lulus' => '2016'],
                    ],
                    'prestasi' => [
                        ['id' => 1, 'judul' => 'Pegawai Teladan', 'keterangan' => 'Penghargaan tahunan unit kerja', 'tanggal' => '2023-12-10'],
                    ],
                ],
                [
                    'id' => 2,
                    'nik' => '1800001',
                    'nama' => 'Dewi Anggraini',
                    'jabatan' => 'Staf Keuangan',
                    'unit_kerja' => 'Divisi Keuangan',
                    'status_peg' => 'PT',
                    'tgl_masuk' => '2018-03-02',
                    'telp' => '081298765432',
                    'alamat' => 'Jl. Sudirman No. 22, Kota B',
                    'keluarga' => [],
                    'golongan' => [['id' => 1, 'golongan' => 'II/D', 'tmt' => '2019-01-01']],
                    'jabatan_riwayat' => [['id' => 1, 'jabatan' => 'Staf Keuangan', 'unit_kerja' => 'Divisi Keuangan', 'tmt' => '2019-01-01']],
                    'pendidikan' => [['id' => 1, 'jenjang' => 'D3', 'jurusan' => 'Akuntansi', 'institusi' => 'Politeknik B', 'tahun_lulus' => '2017']],
                    'prestasi' => [],
                ],
                [
                    'id' => 3,
                    'nik' => '1800003',
                    'nama' => 'Nur Hidayah',
                    'jabatan' => 'Petugas Lapangan',
                    'unit_kerja' => 'Unit Distribusi',
                    'status_peg' => 'TK',
                    'tgl_masuk' => '2022-08-15',
                    'telp' => '081211122233',
                    'alamat' => 'Jl. Melati No. 5, Kota C',
                    'keluarga' => [],
                    'golongan' => [],
                    'jabatan_riwayat' => [],
                    'pendidikan' => [['id' => 1, 'jenjang' => 'SMA/SMK', 'jurusan' => 'IPA', 'institusi' => 'SMA C', 'tahun_lulus' => '2021']],
                    'prestasi' => [],
                ],
            ]);
        }
    }

    protected function all(): array
    {
        $this->seedIfEmpty();

        return session('dummy_pegawai', []);
    }

    protected function save(array $data): void
    {
        session()->put('dummy_pegawai', $data);
    }

    protected function find(int $id): ?array
    {
        return collect($this->all())->firstWhere('id', $id);
    }

    public function index(Request $request)
    {
        $keyword = strtolower($request->get('q', ''));

        $pegawai = collect($this->all())
            ->when($keyword !== '', function ($collection) use ($keyword) {
                return $collection->filter(function ($p) use ($keyword) {
                    return str_contains(strtolower($p['nama']), $keyword)
                        || str_contains(strtolower($p['nik']), $keyword)
                        || str_contains(strtolower($p['unit_kerja']), $keyword);
                });
            })
            ->sortBy('nama')
            ->values();

        return view('pegawai.index', compact('pegawai', 'keyword'));
    }

    public function create()
    {
        return view('pegawai.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        $data = $this->all();

        $newId = $data ? max(array_column($data, 'id')) + 1 : 1;

        $validated['id'] = $newId;
        $validated['keluarga'] = [];
        $validated['golongan'] = [];
        $validated['jabatan_riwayat'] = [];
        $validated['pendidikan'] = [];
        $validated['prestasi'] = [];

        $data[] = $validated;
        $this->save($data);

        return redirect()->route('pegawai.index')->with('success', 'Data pegawai "'.$validated['nama'].'" berhasil ditambahkan.');
    }

    public function show(int $id)
    {
        $pegawai = $this->find($id);

        abort_if(! $pegawai, 404);

        $detailTypes = collect(\App\Http\Controllers\PegawaiDetailController::TYPES)
            ->mapWithKeys(fn ($type) => [$type => \App\Http\Controllers\PegawaiDetailController::fieldConfig($type)])
            ->all();

        return view('pegawai.show', compact('pegawai', 'detailTypes'));
    }

    public function edit(int $id)
    {
        $pegawai = $this->find($id);

        abort_if(! $pegawai, 404);

        return view('pegawai.edit', compact('pegawai'));
    }

    public function update(Request $request, int $id)
    {
        $validated = $this->validateData($request, $id);

        $data = collect($this->all())->map(function ($p) use ($id, $validated) {
            if ($p['id'] === $id) {
                return array_merge($p, $validated);
            }

            return $p;
        })->all();

        $this->save($data);

        return redirect()->route('pegawai.show', $id)->with('success', 'Data pegawai berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $data = collect($this->all())->reject(fn ($p) => $p['id'] === $id)->values()->all();

        $this->save($data);

        return redirect()->route('pegawai.index')->with('success', 'Data pegawai berhasil dihapus.');
    }

    protected function validateData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'nik' => 'required|string|max:20',
            'nama' => 'required|string|max:100',
            'jabatan' => 'required|string|max:100',
            'unit_kerja' => 'required|string|max:100',
            'status_peg' => 'required|in:PT,DI,CP,PH,TK,PN',
            'tgl_masuk' => 'required|date',
            'telp' => 'nullable|string|max:20',
            'alamat' => 'nullable|string|max:255',
        ]);
    }
}
