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
                    ['key' => 'keterangan', 'label' => 'Status Kuliah (khusus Anak)', 'type' => 'select', 'options' => ['Kuliah', 'Tidak Kuliah', '-']],
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

    protected function getDbPegawaiId(int $pegawaiId): ?string
    {
        $pegawaiList = app(PegawaiController::class)->all();
        $pegawai = collect($pegawaiList)->first(function ($p) use ($pegawaiId) {
            return (int) ($p['id'] ?? 0) === $pegawaiId || (string) ($p['db_id'] ?? '') === (string) $pegawaiId;
        });

        return $pegawai['db_id'] ?? null;
    }

    public function store(Request $request, int $pegawaiId, string $type)
    {
        $this->validateType($type);
        $validated = $request->validate($this->rules($type));

        $dbPegawaiId = $this->getDbPegawaiId($pegawaiId);
        $insertedDbId = null;

        if ($dbPegawaiId) {
            try {
                if ($type === 'keluarga') {
                    $insertedDbId = \Illuminate\Support\Facades\DB::table('keluarga')->insertGetId([
                        'pegawai_id' => $dbPegawaiId,
                        'nama' => $validated['nama'],
                        'hubungan' => $validated['hubungan'],
                        'tanggal_lahir' => $validated['tgl_lahir'] ?? $validated['tanggal_lahir'] ?? null,
                        'pekerjaan' => $validated['keterangan'] ?? $validated['pekerjaan'] ?? '-',
                        'created_at' => now(),
                    ]);
                } elseif ($type === 'golongan') {
                    $insertedDbId = \Illuminate\Support\Facades\DB::table('riwayat_golongan')->insertGetId([
                        'pegawai_id' => $dbPegawaiId,
                        'golongan' => $validated['golongan'],
                        'pangkat' => $validated['pangkat'] ?? $validated['golongan'],
                        'tmt' => $validated['tmt'],
                        'no_sk' => $validated['no_sk'] ?? '-',
                        'created_at' => now(),
                    ]);
                } elseif ($type === 'jabatan_riwayat') {
                    $insertedDbId = \Illuminate\Support\Facades\DB::table('riwayat_jabatan')->insertGetId([
                        'pegawai_id' => $dbPegawaiId,
                        'jabatan' => $validated['jabatan'],
                        'unit_kerja' => $validated['unit_kerja'] ?? '-',
                        'tmt' => $validated['tmt'],
                        'no_sk' => $validated['no_sk'] ?? '-',
                        'created_at' => now(),
                    ]);
                } elseif ($type === 'pendidikan') {
                    $insertedDbId = \Illuminate\Support\Facades\DB::table('pendidikan')->insertGetId([
                        'pegawai_id' => $dbPegawaiId,
                        'jenjang' => $validated['jenjang'],
                        'jurusan' => $validated['jurusan'] ?? null,
                        'nama_sekolah' => $validated['institusi'] ?? $validated['nama_sekolah'] ?? null,
                        'tahun_lulus' => $validated['tahun_lulus'] ?? null,
                        'created_at' => now(),
                    ]);
                } elseif ($type === 'prestasi') {
                    $insertedDbId = \Illuminate\Support\Facades\DB::table('prestasi')->insertGetId([
                        'pegawai_id' => $dbPegawaiId,
                        'judul' => $validated['judul'],
                        'keterangan' => $validated['keterangan'] ?? null,
                        'tanggal' => $validated['tanggal'] ?? now()->toDateString(),
                        'created_at' => now(),
                    ]);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("DB store detail $type failed: " . $e->getMessage());
            }
        }

        $data = session('dummy_pegawai', []);

        $data = collect($data)->map(function ($p) use ($pegawaiId, $type, $validated, $insertedDbId) {
            if ($p['id'] === $pegawaiId) {
                $items = $p[$type] ?? [];
                $newId = $insertedDbId ?: ($items ? max(array_column($items, 'id')) + 1 : 1);
                $validated['id'] = $newId;
                $items[] = $validated;
                $p[$type] = $items;
            }

            return $p;
        })->all();

        session()->put('dummy_pegawai', $data);

        // Bersihkan cache agar data terbaru langsung termuat di profil web dan mobile
        \Illuminate\Support\Facades\Cache::forget('simpeg_all_pegawai_list');
        \Illuminate\Support\Facades\Cache::forget('pegawai_master_cache');
        \Illuminate\Support\Facades\Cache::forget('simpeg_dashboard_stats');

        return redirect()->route('pegawai.show', $pegawaiId)->with('success', self::fieldConfig($type)['title'].' berhasil ditambahkan.');
    }

    public function update(Request $request, int $pegawaiId, string $type, int $itemId)
    {
        $this->validateType($type);
        $validated = $request->validate($this->rules($type));

        try {
            if ($type === 'keluarga') {
                \Illuminate\Support\Facades\DB::table('keluarga')->where('id', $itemId)->update([
                    'nama' => $validated['nama'],
                    'hubungan' => $validated['hubungan'],
                    'tanggal_lahir' => $validated['tgl_lahir'] ?? $validated['tanggal_lahir'] ?? null,
                    'pekerjaan' => $validated['keterangan'] ?? $validated['pekerjaan'] ?? '-',
                ]);
            } elseif ($type === 'golongan') {
                \Illuminate\Support\Facades\DB::table('riwayat_golongan')->where('id', $itemId)->update([
                    'golongan' => $validated['golongan'],
                    'pangkat' => $validated['pangkat'] ?? $validated['golongan'],
                    'tmt' => $validated['tmt'],
                ]);
            } elseif ($type === 'jabatan_riwayat') {
                \Illuminate\Support\Facades\DB::table('riwayat_jabatan')->where('id', $itemId)->update([
                    'jabatan' => $validated['jabatan'],
                    'unit_kerja' => $validated['unit_kerja'] ?? '-',
                    'tmt' => $validated['tmt'],
                ]);
            } elseif ($type === 'pendidikan') {
                \Illuminate\Support\Facades\DB::table('pendidikan')->where('id', $itemId)->update([
                    'jenjang' => $validated['jenjang'],
                    'jurusan' => $validated['jurusan'] ?? null,
                    'nama_sekolah' => $validated['institusi'] ?? $validated['nama_sekolah'] ?? null,
                    'tahun_lulus' => $validated['tahun_lulus'] ?? null,
                ]);
            } elseif ($type === 'prestasi') {
                \Illuminate\Support\Facades\DB::table('prestasi')->where('id', $itemId)->update([
                    'judul' => $validated['judul'],
                    'keterangan' => $validated['keterangan'] ?? null,
                    'tanggal' => $validated['tanggal'] ?? null,
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("DB update detail $type failed: " . $e->getMessage());
        }

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

        \Illuminate\Support\Facades\Cache::forget('simpeg_all_pegawai_list');
        \Illuminate\Support\Facades\Cache::forget('pegawai_master_cache');
        \Illuminate\Support\Facades\Cache::forget('simpeg_dashboard_stats');

        return redirect()->route('pegawai.show', $pegawaiId)->with('success', self::fieldConfig($type)['title'].' berhasil diperbarui.');
    }

    public function destroy(int $pegawaiId, string $type, int $itemId)
    {
        $this->validateType($type);

        $table = match ($type) {
            'keluarga' => 'keluarga',
            'golongan' => 'riwayat_golongan',
            'jabatan_riwayat' => 'riwayat_jabatan',
            'pendidikan' => 'pendidikan',
            'prestasi' => 'prestasi',
            default => null,
        };

        if ($table) {
            try {
                \Illuminate\Support\Facades\DB::table($table)->where('id', $itemId)->delete();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("DB destroy detail $type failed: " . $e->getMessage());
            }
        }

        $data = session('dummy_pegawai', []);

        $data = collect($data)->map(function ($p) use ($pegawaiId, $type, $itemId) {
            if ($p['id'] === $pegawaiId) {
                $p[$type] = collect($p[$type] ?? [])->reject(fn ($item) => $item['id'] === $itemId)->values()->all();
            }

            return $p;
        })->all();

        session()->put('dummy_pegawai', $data);

        \Illuminate\Support\Facades\Cache::forget('simpeg_all_pegawai_list');
        \Illuminate\Support\Facades\Cache::forget('pegawai_master_cache');
        \Illuminate\Support\Facades\Cache::forget('simpeg_dashboard_stats');

        return redirect()->route('pegawai.show', $pegawaiId)->with('success', 'Data berhasil dihapus.');
    }
}
