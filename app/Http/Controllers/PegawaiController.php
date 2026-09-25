<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PegawaiController extends Controller
{
    /**
     * Modul Master Data Pegawai.
     * Terhubung langsung dengan tabel pegawai di database Supabase PostgreSQL.
     */
    protected static ?array $memoryCache = null;

    public function all(): array
    {
        if (static::$memoryCache !== null) {
            return static::$memoryCache;
        }

        $list = \Illuminate\Support\Facades\Cache::remember('simpeg_all_pegawai_list', 120, function () {
            try {
                $dbPegawai = \Illuminate\Support\Facades\DB::table('pegawai')->select('id', 'nik', 'name', 'gelar', 'jabatan', 'unit_kerja', 'status', 'no_telp', 'alamat')->get();
                if ($dbPegawai->isNotEmpty()) {
                    $res = [];
                    $index = 1;
                    foreach ($dbPegawai as $sp) {
                        $res[] = [
                            'id' => $index++,
                            'db_id' => $sp->id,
                            'nik' => (string) $sp->nik,
                            'nama' => $sp->name ?? 'Pegawai',
                            'gelar' => $sp->gelar ?? '',
                            'jabatan' => $sp->jabatan ?? 'Staf',
                            'unit_kerja' => $sp->unit_kerja ?? 'Kantor Pusat',
                            'status_peg' => $sp->status ?? 'PT',
                            'tgl_masuk' => date('Y-m-d'),
                            'telp' => $sp->no_telp ?? '-',
                            'alamat' => $sp->alamat ?? '-',
                            'keluarga' => [],
                            'golongan' => [],
                            'jabatan_riwayat' => [],
                            'pendidikan' => [],
                            'prestasi' => [],
                        ];
                    }
                    return $res;
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('DB pegawai read failed: ' . $e->getMessage());
            }

            return [];
        });

        static::$memoryCache = $list ?? [];
        return static::$memoryCache;
    }

    protected function save(array $data): void
    {
        static::$memoryCache = $data;
        \Illuminate\Support\Facades\Cache::forget('simpeg_all_pegawai_list');
        \Illuminate\Support\Facades\Cache::forget('simpeg_dashboard_stats');
    }

    public function find(int $id): ?array
    {
        $pegawai = collect($this->all())->firstWhere('id', $id);
        if ($pegawai) {
            // Muat relasi detail hanya untuk pegawai yang dibuka ini (on-demand)
            if (!empty($pegawai['db_id'])) {
                try {
                    $pegawai['keluarga'] = \Illuminate\Support\Facades\DB::table('keluarga')
                        ->where('pegawai_id', $pegawai['db_id'])
                        ->get()
                        ->map(function ($r) {
                            $arr = (array) $r;
                            $arr['tgl_lahir'] = $arr['tgl_lahir'] ?? $arr['tanggal_lahir'] ?? '-';
                            $arr['tanggal_lahir'] = $arr['tanggal_lahir'] ?? $arr['tgl_lahir'] ?? '-';
                            $arr['keterangan'] = $arr['keterangan'] ?? $arr['pekerjaan'] ?? '-';
                            $arr['pekerjaan'] = $arr['pekerjaan'] ?? $arr['keterangan'] ?? '-';
                            return $arr;
                        })
                        ->toArray();

                    $pegawai['pendidikan'] = \Illuminate\Support\Facades\DB::table('pendidikan')
                        ->where('pegawai_id', $pegawai['db_id'])
                        ->get()
                        ->map(function ($r) {
                            $arr = (array) $r;
                            $arr['institusi'] = $arr['institusi'] ?? $arr['nama_sekolah'] ?? '-';
                            $arr['nama_sekolah'] = $arr['nama_sekolah'] ?? $arr['institusi'] ?? '-';
                            return $arr;
                        })
                        ->toArray();

                    $pegawai['golongan'] = \Illuminate\Support\Facades\DB::table('riwayat_golongan')
                        ->where('pegawai_id', $pegawai['db_id'])
                        ->get()
                        ->map(function ($r) {
                            $arr = (array) $r;
                            $arr['pangkat'] = $arr['pangkat'] ?? $arr['golongan'] ?? '-';
                            return $arr;
                        })
                        ->toArray();

                    $pegawai['jabatan_riwayat'] = \Illuminate\Support\Facades\DB::table('riwayat_jabatan')
                        ->where('pegawai_id', $pegawai['db_id'])
                        ->get()
                        ->map(function ($r) {
                            $arr = (array) $r;
                            $arr['unit_kerja'] = $arr['unit_kerja'] ?? '-';
                            return $arr;
                        })
                        ->toArray();

                    $pegawai['prestasi'] = \Illuminate\Support\Facades\DB::table('prestasi')
                        ->where('pegawai_id', $pegawai['db_id'])
                        ->orderByDesc('id')
                        ->get()
                        ->map(function ($r) {
                            $arr = (array) $r;
                            if (!empty($arr['keterangan']) && str_starts_with(trim($arr['keterangan']), '{')) {
                                $dec = json_decode($arr['keterangan'], true);
                                if (is_array($dec) && !empty($dec['desc'])) {
                                    $arr['keterangan'] = $dec['desc'];
                                }
                            }
                            return $arr;
                        })
                        ->toArray();
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('DB load pegawai detail relations failed: ' . $e->getMessage());
                }
            }

            // Muat dokumen resmi riil dari database (tabel dokumen_pegawai)
            $dbId = $pegawai['db_id'] ?? null;
            $intId = (string) ($pegawai['id'] ?? '');
            $nik = (string) ($pegawai['nik'] ?? '');

            try {
                $docs = \Illuminate\Support\Facades\DB::table('dokumen_pegawai')
                    ->where(function ($q) use ($dbId, $intId, $nik) {
                        if ($dbId) $q->where('pegawai_id', $dbId);
                        if ($intId) $q->orWhere('pegawai_id', $intId);
                        if ($nik) $q->orWhere('pegawai_id', $nik);
                    })
                    ->get();

                $skDoc = $docs->first(fn ($d) => in_array(strtolower($d->kategori ?? ''), ['sk', 'surat_kerja'], true));
                $diklatDoc = $docs->first(fn ($d) => in_array(strtolower($d->kategori ?? ''), ['diklat', 'surat_diklat'], true));

                $pegawai['surat_kerja'] = $skDoc ? [
                    'id' => $skDoc->id,
                    'nomor' => $skDoc->nomor,
                    'judul' => $skDoc->judul,
                    'tgl_terbit' => !empty($skDoc->created_at) ? substr((string)$skDoc->created_at, 0, 10) : null,
                    'file_name' => $skDoc->file_nama,
                    'file_url' => $this->normalizeFileUrl($skDoc->file_url),
                ] : null;

                $pegawai['surat_diklat'] = $diklatDoc ? [
                    'id' => $diklatDoc->id,
                    'nomor' => $diklatDoc->nomor,
                    'judul' => $diklatDoc->judul,
                    'tgl_terbit' => !empty($diklatDoc->created_at) ? substr((string)$diklatDoc->created_at, 0, 10) : null,
                    'file_name' => $diklatDoc->file_nama,
                    'file_url' => $this->normalizeFileUrl($diklatDoc->file_url),
                ] : null;
            } catch (\Throwable $e) {
                $pegawai['surat_kerja'] = null;
                $pegawai['surat_diklat'] = null;
            }
        }
        return $pegawai;
    }

    public function index(Request $request)
    {
        $keyword = strtolower($request->get('q', ''));

        $pegawai = collect($this->all())
            ->where('status_peg', '!=', 'PN') // pegawai pensiun tidak ditampilkan di daftar utama
            ->when($keyword !== '', function ($collection) use ($keyword) {
                return $collection->filter(function ($p) use ($keyword) {
                    return str_contains(strtolower($p['nama'] ?? ''), $keyword)
                        || str_contains(strtolower($p['nik'] ?? ''), $keyword)
                        || str_contains(strtolower($p['jabatan'] ?? ''), $keyword)
                        || str_contains(strtolower($p['unit_kerja'] ?? ''), $keyword);
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

        $newUuid = (string) Str::uuid();

        // 1. Simpan ke database Supabase PostgreSQL
        try {
            \Illuminate\Support\Facades\DB::table('pegawai')->insert([
                'id' => $newUuid,
                'nik' => $validated['nik'],
                'name' => $validated['nama'],
                'jabatan' => $validated['jabatan'],
                'unit_kerja' => $validated['unit_kerja'],
                'status' => $validated['status_peg'],
                'no_telp' => $validated['telp'] ?? null,
                'alamat' => $validated['alamat'] ?? null,
                'role' => 'pegawai',
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('DB pegawai insert failed: ' . $e->getMessage());
        }

        // 2. Bersihkan cache agar segera terupdate di seluruh laptop dan aplikasi mobile
        static::$memoryCache = null;
        \Illuminate\Support\Facades\Cache::forget('simpeg_all_pegawai_list');
        \Illuminate\Support\Facades\Cache::forget('simpeg_dashboard_stats');
        \Illuminate\Support\Facades\Cache::forget('pegawai_master_cache');

        return redirect()->route('pegawai.index')->with('success', 'Data pegawai "'.$validated['nama'].'" berhasil ditambahkan ke database.');
    }

    public function show(int $id)
    {
        $pegawai = $this->find($id);

        abort_if(! $pegawai, 404);

        // Role Pegawai (5) cuma boleh lihat datanya sendiri, tidak boleh
        // intip data pegawai lain lewat tebak-tebak URL.
        if (session('simpeg_user.userlevel') === '5' && $pegawai['nik'] !== session('simpeg_user.nik')) {
            abort(403, 'Kamu hanya bisa melihat data diri sendiri.');
        }

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
        $pegawai = $this->find($id);
        abort_if(! $pegawai, 404);

        $dbId = $pegawai['db_id'] ?? null;
        if ($dbId) {
            try {
                \Illuminate\Support\Facades\DB::table('pegawai')->where('id', $dbId)->update([
                    'nik' => $validated['nik'],
                    'name' => $validated['nama'],
                    'jabatan' => $validated['jabatan'],
                    'unit_kerja' => $validated['unit_kerja'],
                    'status' => $validated['status_peg'],
                    'no_telp' => $validated['telp'] ?? null,
                    'alamat' => $validated['alamat'] ?? null,
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('DB pegawai update failed: ' . $e->getMessage());
            }
        }

        static::$memoryCache = null;
        \Illuminate\Support\Facades\Cache::forget('simpeg_all_pegawai_list');
        \Illuminate\Support\Facades\Cache::forget('simpeg_dashboard_stats');
        \Illuminate\Support\Facades\Cache::forget('pegawai_master_cache');

        return redirect()->route('pegawai.show', $id)->with('success', 'Data pegawai berhasil diperbarui di database.');
    }

    public function destroy(int $id)
    {
        $pegawai = $this->find($id);
        abort_if(! $pegawai, 404);

        $dbId = $pegawai['db_id'] ?? null;
        if ($dbId) {
            try {
                \Illuminate\Support\Facades\DB::table('pegawai')->where('id', $dbId)->delete();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('DB pegawai delete failed: ' . $e->getMessage());
            }
        }

        static::$memoryCache = null;
        \Illuminate\Support\Facades\Cache::forget('simpeg_all_pegawai_list');
        \Illuminate\Support\Facades\Cache::forget('simpeg_dashboard_stats');
        \Illuminate\Support\Facades\Cache::forget('pegawai_master_cache');

        return redirect()->route('pegawai.index')->with('success', 'Data pegawai berhasil dihapus dari database.');
    }

    /**
     * Mengangkat Calon Pegawai (CP) jadi Pegawai Tetap (PT), sekaligus ganti NIK.
     * Menggantikan update_nik_capeg_to_peg.php di sistem lama.
     */
    public function promoteToTetap(Request $request, int $id)
    {
        $pegawai = $this->find($id);
        abort_if(! $pegawai, 404);
        abort_unless($pegawai['status_peg'] === 'CP', 400, 'Hanya Calon Pegawai yang bisa diangkat jadi Pegawai Tetap.');

        $validated = $request->validate([
            'nik_baru' => 'required|string|max:20',
        ]);

        $dbId = $pegawai['db_id'] ?? null;
        if ($dbId) {
            try {
                \Illuminate\Support\Facades\DB::table('pegawai')->where('id', $dbId)->update([
                    'nik' => $validated['nik_baru'],
                    'status' => 'PT',
                ]);
            } catch (\Throwable $e) {}
        }

        static::$memoryCache = null;
        \Illuminate\Support\Facades\Cache::forget('simpeg_all_pegawai_list');
        \Illuminate\Support\Facades\Cache::forget('simpeg_dashboard_stats');
        \Illuminate\Support\Facades\Cache::forget('pegawai_master_cache');

        return redirect()->route('pegawai.show', $id)->with('success', 'Pegawai berhasil diangkat menjadi Pegawai Tetap dengan NIK baru: '.$validated['nik_baru']);
    }

    /**
     * Lap. Anak Diatas 21 - disamakan dengan sistem lama (cetak_laporan_anak.php).
     * Query aslinya:
     *   SELECT * FROM tbl_keluarga WHERE YEAR(tgl_lahir) <= (tahun ini - 21)
     *   AND status_keluarga='Anak' AND keterangan='Tidak Kuliah' AND status_aktif='Y'
     *
     * Jadi laporan ini menyaring anak pegawai yang usianya sudah di atas 21
     * tahun DAN statusnya "Tidak Kuliah" - dipakai HRD untuk mengecek anak
     * mana yang tunjangan keluarganya perlu dihentikan (biasanya tunjangan
     * anak berhenti di usia 21 kecuali masih kuliah).
     */
    public function laporanAnakDiatas21()
    {
        $batasUsia = now()->subYears(21);

        // Ambil semua keluarga berhubungan 'Anak' langsung dari database,
        // karena $this->all() tidak memuat relasi keluarga (selalu []).
        try {
            $keluargaRows = \Illuminate\Support\Facades\DB::table('keluarga')
                ->join('pegawai', 'keluarga.pegawai_id', '=', 'pegawai.id')
                ->select(
                    'keluarga.*',
                    'pegawai.nik as nik_pegawai',
                    'pegawai.name as nama_pegawai'
                )
                ->whereRaw("LOWER(COALESCE(keluarga.hubungan, '')) = ?", ['anak'])
                ->get();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('DB keluarga query for laporan anak 21 failed: ' . $e->getMessage());
            $keluargaRows = collect();
        }

        $data = $keluargaRows
            ->map(function ($row) {
                $arr = (array) $row;
                $arr['tgl_lahir'] = $arr['tgl_lahir'] ?? $arr['tanggal_lahir'] ?? null;
                $arr['keterangan'] = $arr['keterangan'] ?? $arr['pekerjaan'] ?? '-';
                $arr['nama'] = $arr['nama'] ?? '-';
                return $arr;
            })
            ->filter(function ($anak) use ($batasUsia) {
                // Filter: keterangan = 'Tidak Kuliah' dan usia > 21 tahun
                $keterangan = $anak['keterangan'] ?? '-';
                $tglLahir = $anak['tgl_lahir'] ?? null;
                if (empty($tglLahir) || $tglLahir === '-') return false;

                try {
                    return $keterangan === 'Tidak Kuliah'
                        && \Illuminate\Support\Carbon::parse($tglLahir)->lte($batasUsia);
                } catch (\Throwable $e) {
                    return false;
                }
            })
            ->map(function ($anak) {
                try {
                    $anak['usia'] = (int) \Illuminate\Support\Carbon::parse($anak['tgl_lahir'])->diffInYears(now());
                } catch (\Throwable $e) {
                    $anak['usia'] = 0;
                }
                return $anak;
            })
            ->sortBy('nama_pegawai')
            ->values();

        return view('pegawai.laporan-anak', compact('data'));
    }

    /**
     * Data Pegawai Per Unit Kerja — dropdown pilih unit kerja, tampilkan daftar pegawai.
     * Disamakan dengan daftar_pegawai_unit_kerja.php di sistem lama.
     */
    public function perUnitKerja(Request $request)
    {
        $pegawai = collect($this->all());

        // Ambil daftar unit kerja unik
        $unitKerjaList = $pegawai->pluck('unit_kerja')->unique()->sort()->values();

        $selected = $request->unit_kerja;
        $filtered = collect();

        if ($selected) {
            $filtered = $pegawai->where('unit_kerja', $selected)
                ->where('status_peg', '!=', 'PN')
                ->map(function ($p) {
                    $statusMap = ['CP' => 'Capeg', 'PH' => 'Honorer', 'PK' => 'Kontrak', 'DI' => 'Direksi', 'TK' => 'Tenaga Kontrak'];
                    $p['status_label'] = $statusMap[$p['status_peg']] ?? 'Tetap';

                    if (! empty($p['tgl_masuk'])) {
                        $masuk = \Illuminate\Support\Carbon::parse($p['tgl_masuk']);
                        $diff = $masuk->diff(now());
                        $p['masa_kerja'] = $diff->y . ' Thn, ' . $diff->m . ' Bln';
                    } else {
                        $p['masa_kerja'] = '-';
                    }
                    return $p;
                })
                ->sortBy('jabatan')
                ->values();
        }

        return view('pegawai.per-unit-kerja', compact('unitKerjaList', 'selected', 'filtered'));
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

    protected function normalizeFileUrl(?string $url): string
    {
        if (empty($url) || $url === '#') {
            return '#';
        }
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '';
        if (! empty($path) && str_starts_with($path, '/uploads/')) {
            return asset(ltrim($path, '/'));
        }
        return $url;
    }
}
