<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\PegawaiController;

class PejabatTtdController extends Controller
{
    /**
     * Modul Pengaturan Pejabat Penandatangan Laporan Dinamis (tbl_ttd)
     * Untuk Role 1 (SDM), Role 2 (Keuangan), & Role 7 (Direksi).
     *
     * Disamakan dengan sistem lama:
     * - edit_ttd_keu.php (id=1: Laporan Keuangan & Potongan)
     * - edit_ttd_gaji.php (id=2: Laporan Penggajian & SDM)
     * Ditambah konfigurasi dinamis untuk THR, Gaji 13, dan Laporan Lainnya.
     */

    public static function getTtd(string|int $kodeOrId = 'keuangan'): array
    {
        $cacheKey = "simpeg_ttd_" . (is_numeric($kodeOrId) ? "id_{$kodeOrId}" : $kodeOrId);

        return Cache::remember($cacheKey, 3600, function () use ($kodeOrId) {
            try {
                $query = DB::table('tbl_ttd');
                if (is_numeric($kodeOrId)) {
                    $row = $query->where('id', (int) $kodeOrId)->first();
                } else {
                    $row = $query->where('kode', (string) $kodeOrId)->first();
                }

                if ($row) {
                    return [
                        'id'         => $row->id,
                        'kode'       => $row->kode,
                        'judul'      => $row->judul,
                        'kota'       => $row->kota ?? 'Indramayu',
                        'mengetahui' => [
                            'label'   => $row->label1 ?? 'MENGETAHUI',
                            'jabatan' => $row->jabatan1 ?? 'Direktur Utama',
                            'nama'    => $row->nama1,
                            'nik'     => $row->nik1,
                        ],
                        'menyetujui' => [
                            'label'   => $row->label2 ?? 'DISETUJUI',
                            'jabatan' => $row->jabatan2 ?? 'Direktur Umum',
                            'nama'    => $row->nama2,
                            'nik'     => $row->nik2,
                        ],
                        'pembuat'    => [
                            'label'   => $row->label3 ?? 'DIBUAT OLEH',
                            'jabatan' => $row->jabatan3 ?? 'Manajer Terkait',
                            'nama'    => $row->nama3,
                            'nik'     => $row->nik3,
                        ],
                    ];
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('PejabatTtdController::getTtd error: ' . $e->getMessage());
            }

            // Fallback aman jika tabel belum terbaca
            return [
                'kota'       => 'Indramayu',
                'mengetahui' => [
                    'label'   => 'MENGETAHUI',
                    'jabatan' => 'Direktur Utama',
                    'nama'    => 'DRS. H. TATANG SUTARDI, S.SOS, M.SI',
                    'nik'     => '17 11 113',
                ],
                'menyetujui' => [
                    'label'   => 'DISETUJUI',
                    'jabatan' => 'Direktur Umum',
                    'nama'    => 'SUWARYO, ST',
                    'nik'     => '17 11 202',
                ],
                'pembuat'    => [
                    'label'   => 'DIBUAT OLEH',
                    'jabatan' => 'Manajer Bidang Keuangan',
                    'nama'    => 'YAYAH KHAERIYAH, S.T.',
                    'nik'     => '17 11 296',
                ],
            ];
        });
    }

    public static function clearCache(?string $kode = null): void
    {
        Cache::forget('simpeg_ttd_all');
        if ($kode) {
            Cache::forget("simpeg_ttd_{$kode}");
        }
        Cache::forget('simpeg_ttd_keuangan');
        Cache::forget('simpeg_ttd_gaji');
        Cache::forget('simpeg_ttd_thr');
        Cache::forget('simpeg_ttd_gaji13');
        Cache::forget('simpeg_ttd_id_1');
        Cache::forget('simpeg_ttd_id_2');
        Cache::forget('simpeg_ttd_id_3');
        Cache::forget('simpeg_ttd_id_4');
    }

    public function index(Request $request)
    {
        $ttdList = DB::table('tbl_ttd')->orderBy('id', 'asc')->get();
        $pegawaiList = app(PegawaiController::class)->all();

        return view('pejabat-ttd.index', compact('ttdList', 'pegawaiList'));
    }

    public function edit($id)
    {
        $ttd = DB::table('tbl_ttd')->where('id', $id)->first();
        if (!$ttd) {
            return redirect()->route('pejabat-ttd.index')->withErrors(['error' => 'Data konfigurasi tanda tangan tidak ditemukan.']);
        }

        $pegawaiList = app(PegawaiController::class)->all();

        return view('pejabat-ttd.edit', compact('ttd', 'pegawaiList'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'judul'    => 'required|string|max:150',
            'kota'     => 'required|string|max:50',
            'label1'   => 'required|string|max:50',
            'jabatan1' => 'required|string|max:100',
            'nama1'    => 'required|string|max:150',
            'nik1'     => 'required|string|max:50',

            'label2'   => 'required|string|max:50',
            'jabatan2' => 'required|string|max:100',
            'nama2'    => 'required|string|max:150',
            'nik2'     => 'required|string|max:50',

            'label3'   => 'required|string|max:50',
            'jabatan3' => 'required|string|max:100',
            'nama3'    => 'required|string|max:150',
            'nik3'     => 'required|string|max:50',
        ]);

        $row = DB::table('tbl_ttd')->where('id', $id)->first();
        if (!$row) {
            return redirect()->route('pejabat-ttd.index')->withErrors(['error' => 'Data tidak ditemukan.']);
        }

        DB::table('tbl_ttd')->where('id', $id)->update(array_merge($validated, [
            'updated_at' => now(),
        ]));

        static::clearCache($row->kode);

        return redirect()->route('pejabat-ttd.index')->with('success', "Konfigurasi pejabat penandatangan '{$validated['judul']}' berhasil diperbarui!");
    }
}

