<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PegawaiDetailController extends Controller
{
    /**
     * Controller ini menangani 5 jenis "riwayat" pegawai sekaligus
     * (Keluarga, Golongan, Jabatan, Pendidikan, Prestasi) - dulunya di sistem
     * lama ini 15 file terpisah (tambah/edit/hapus x 5 jenis), sekarang
     * digabung jadi 1 controller dengan parameter $type.
     *
     * Data masih dummy (session), lihat catatan yang sama di PegawaiController.
     */
    public const TYPES = ['keluarga', 'golongan', 'jabatan_riwayat', 'pendidikan', 'prestasi'];

    /**
     * Konfigurasi field per jenis - dipakai buat generate form modal secara dinamis.
     */
    public static function fieldConfig(string $type): array
    {
        return match ($type) {
            'keluarga' => [
                'title' => 'Data Keluarga',
                'fields' => [
                    ['key' => 'nama', 'label' => 'Nama', 'type' => 'text'],
                    ['key' => 'hubungan', 'label' => 'Hubungan', 'type' => 'select', 'options' => ['Istri/Suami', 'Anak', 'Orang Tua']],
                    ['key' => 'tgl_lahir', 'label' => 'Tanggal Lahir', 'type' => 'date'],
                ],
            ],
            'golongan' => [
                'title' => 'Riwayat Golongan',
                'fields' => [
                    ['key' => 'golongan', 'label' => 'Golongan', 'type' => 'text'],
                    ['key' => 'tmt', 'label' => 'TMT (Terhitung Mulai Tanggal)', 'type' => 'date'],
                ],
            ],
            'jabatan_riwayat' => [
                'title' => 'Riwayat Jabatan',
                'fields' => [
                    ['key' => 'jabatan', 'label' => 'Jabatan', 'type' => 'text'],
                    ['key' => 'unit_kerja', 'label' => 'Unit Kerja', 'type' => 'text'],
                    ['key' => 'tmt', 'label' => 'TMT', 'type' => 'date'],
                ],
            ],
            'pendidikan' => [
                'title' => 'Riwayat Pendidikan',
                'fields' => [
                    ['key' => 'jenjang', 'label' => 'Jenjang', 'type' => 'select', 'options' => ['SMA/SMK', 'D3', 'S1', 'S2', 'S3']],
                    ['key' => 'jurusan', 'label' => 'Jurusan', 'type' => 'text'],
                    ['key' => 'institusi', 'label' => 'Institusi', 'type' => 'text'],
                    ['key' => 'tahun_lulus', 'label' => 'Tahun Lulus', 'type' => 'text'],
                ],
            ],
            'prestasi' => [
                'title' => 'Prestasi',
                'fields' => [
                    ['key' => 'judul', 'label' => 'Judul Prestasi', 'type' => 'text'],
                    ['key' => 'keterangan', 'label' => 'Keterangan', 'type' => 'textarea'],
                    ['key' => 'tanggal', 'label' => 'Tanggal', 'type' => 'date'],
                ],
            ],
            default => abort(404),
        };
    }

    protected function validateType(string $type): void
    {
        abort_unless(in_array($type, self::TYPES, true), 404);
    }

    protected function rules(string $type): array
    {
        $rules = [];

        foreach (self::fieldConfig($type)['fields'] as $field) {
            $rules[$field['key']] = $field['type'] === 'textarea' ? 'nullable|string' : 'required|string|max:150';
        }

        return $rules;
    }

    public function store(Request $request, int $pegawaiId, string $type)
    {
        $this->validateType($type);
        $validated = $request->validate($this->rules($type));

        $data = session('dummy_pegawai', []);

        $data = collect($data)->map(function ($p) use ($pegawaiId, $type, $validated) {
            if ($p['id'] === $pegawaiId) {
                $items = $p[$type] ?? [];
                $newId = $items ? max(array_column($items, 'id')) + 1 : 1;
                $validated['id'] = $newId;
                $items[] = $validated;
                $p[$type] = $items;
            }

            return $p;
        })->all();

        session()->put('dummy_pegawai', $data);

        return redirect()->route('pegawai.show', $pegawaiId)->with('success', self::fieldConfig($type)['title'].' berhasil ditambahkan.');
    }

    public function update(Request $request, int $pegawaiId, string $type, int $itemId)
    {
        $this->validateType($type);
        $validated = $request->validate($this->rules($type));

        $data = session('dummy_pegawai', []);

        $data = collect($data)->map(function ($p) use ($pegawaiId, $type, $itemId, $validated) {
            if ($p['id'] === $pegawaiId) {
                $p[$type] = collect($p[$type] ?? [])->map(function ($item) use ($itemId, $validated) {
                    if ($item['id'] === $itemId) {
                        return array_merge($item, $validated);
                    }

                    return $item;
                })->all();
            }

            return $p;
        })->all();

        session()->put('dummy_pegawai', $data);

        return redirect()->route('pegawai.show', $pegawaiId)->with('success', self::fieldConfig($type)['title'].' berhasil diperbarui.');
    }

    public function destroy(int $pegawaiId, string $type, int $itemId)
    {
        $this->validateType($type);

        $data = session('dummy_pegawai', []);

        $data = collect($data)->map(function ($p) use ($pegawaiId, $type, $itemId) {
            if ($p['id'] === $pegawaiId) {
                $p[$type] = collect($p[$type] ?? [])->reject(fn ($item) => $item['id'] === $itemId)->values()->all();
            }

            return $p;
        })->all();

        session()->put('dummy_pegawai', $data);

        return redirect()->route('pegawai.show', $pegawaiId)->with('success', 'Data berhasil dihapus.');
    }
}
