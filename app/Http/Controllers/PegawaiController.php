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

        $cached = \Illuminate\Support\Facades\Cache::get('simpeg_all_pegawai_list');
        if (is_array($cached) && ! empty($cached)) {
            static::$memoryCache = $cached;
            return static::$memoryCache;
        }

        $list = [];
        try {
            $dbPegawai = \Illuminate\Support\Facades\DB::table('pegawai')
                ->select(
                    'id',
                    'nik',
                    'name',
                    'gelar',
                    'jabatan',
                    'unit_kerja',
                    'golongan',
                    'golongan_detail',
                    'status',
                    'tempat_tanggal_lahir',
                    'status_pernikahan',
                    'no_telp',
                    'alamat',
                    'email',
                    'foto_url',
                    'created_at'
                )
                ->orderBy('nik', 'asc')
                ->get();
            if ($dbPegawai->isNotEmpty()) {
                $index = 1;
                foreach ($dbPegawai as $sp) {
                    $rawNik = preg_replace('/\s+/', '', trim((string) $sp->nik));
                    $tglMasuk = ! empty($sp->created_at) ? substr((string) $sp->created_at, 0, 10) : date('Y-m-d');
                    if (strlen($rawNik) >= 4 && ctype_digit(substr($rawNik, 0, 4))) {
                        $yy = (int) substr($rawNik, 0, 2);
                        $mm = (int) substr($rawNik, 2, 2);
                        $year = ($yy > 50) ? (1900 + $yy) : (2000 + $yy);
                        if ($mm >= 1 && $mm <= 12 && $year <= (int) date('Y')) {
                            $tglMasuk = sprintf('%04d-%02d-01', $year, $mm);
                        }
                    }

                    $list[] = [
                        'id' => $index++,
                        'db_id' => $sp->id,
                        'nik' => (string) $sp->nik,
                        'nama' => $sp->name ?? 'Pegawai',
                        'gelar' => $sp->gelar ?? '',
                        'jabatan' => $sp->jabatan ?? 'Staf',
                        'unit_kerja' => $sp->unit_kerja ?? 'Kantor Pusat',
                        'golongan_utama' => $sp->golongan ?? '',
                        'golongan_detail' => $sp->golongan_detail ?? '',
                        'status_peg' => $sp->status ?? 'PT',
                        'tempat_tanggal_lahir' => $sp->tempat_tanggal_lahir ?? '',
                        'status_pernikahan' => $sp->status_pernikahan ?? '',
                        'tgl_masuk' => $tglMasuk,
                        'telp' => $sp->no_telp ?? '-',
                        'alamat' => $sp->alamat ?? '-',
                        'email' => $sp->email ?? '',
                        'foto_url' => $sp->foto_url ?? null,
                        'created_at' => $sp->created_at ?? null,
                        'keluarga' => [],
                        'golongan' => [],
                        'jabatan_riwayat' => [],
                        'pendidikan' => [],
                        'prestasi' => [],
                    ];
                }
                \Illuminate\Support\Facades\Cache::put('simpeg_all_pegawai_list', $list, 120);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('DB pegawai read failed: ' . $e->getMessage());
        }

        static::$memoryCache = $list;
        return static::$memoryCache;
    }

    protected function save(array $data): void
    {
        static::$memoryCache = $data;
        \Illuminate\Support\Facades\Cache::forget('simpeg_all_pegawai_list');
        \Illuminate\Support\Facades\Cache::forget('simpeg_dashboard_stats');
    }

    public function find(int|string $id): ?array
    {
        $all = collect($this->all());
        $pegawai = is_numeric($id) ? $all->firstWhere('id', (int) $id) : null;
        if (! $pegawai) {
            $pegawai = $all->firstWhere('nik', (string) $id) ?? $all->firstWhere('db_id', (string) $id);
        }
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
                        ->orderByDesc('tahun_lulus')
                        ->orderByDesc('id')
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
                        ->orderByDesc('tmt')
                        ->orderByDesc('id')
                        ->get()
                        ->map(function ($r) {
                            $arr = (array) $r;
                            $arr['pangkat'] = $arr['pangkat'] ?? $arr['golongan'] ?? '-';
                            return $arr;
                        })
                        ->toArray();

                    $pegawai['jabatan_riwayat'] = \Illuminate\Support\Facades\DB::table('riwayat_jabatan')
                        ->where('pegawai_id', $pegawai['db_id'])
                        ->orderByDesc('tmt')
                        ->orderByDesc('id')
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
        $keyword = strtolower(trim((string)$request->get('q', '')));
        $sort = strtolower(trim((string)$request->get('sort', 'nama_asc')));

        $collection = collect($this->all())
            ->where('status_peg', '!=', 'PN') // pegawai pensiun tidak ditampilkan di daftar utama
            ->when($keyword !== '', function ($coll) use ($keyword) {
                return $coll->filter(function ($p) use ($keyword) {
                    return str_contains(strtolower($p['nama'] ?? ''), $keyword)
                        || str_contains(strtolower($p['nik'] ?? ''), $keyword)
                        || str_contains(strtolower($p['jabatan'] ?? ''), $keyword)
                        || str_contains(strtolower($p['unit_kerja'] ?? ''), $keyword);
                });
            });

        // Opsi Pengurutan (Ascending / Descending)
        if ($sort === 'terbaru') {
            $collection = $collection->sortByDesc(fn ($p) => ($p['created_at'] ?? '') . '_' . str_pad((string)($p['id'] ?? 0), 6, '0', STR_PAD_LEFT))->values();
        } elseif ($sort === 'terlama') {
            $collection = $collection->sortBy(fn ($p) => ($p['created_at'] ?? '') . '_' . str_pad((string)($p['id'] ?? 0), 6, '0', STR_PAD_LEFT))->values();
        } elseif ($sort === 'nik_asc') {
            $collection = $collection->sortBy('nik')->values();
        } elseif ($sort === 'nik_desc') {
            $collection = $collection->sortByDesc('nik')->values();
        } elseif ($sort === 'nama_desc') {
            $collection = $collection->sortByDesc('nama', SORT_NATURAL | SORT_FLAG_CASE)->values();
        } else {
            // Default nama_asc (A - Z)
            $collection = $collection->sortBy('nama', SORT_NATURAL | SORT_FLAG_CASE)->values();
        }

        // Pagination 20 Data per Halaman
        $perPage = 20;
        $currentPage = (int) $request->query('page', 1);
        if ($currentPage < 1) $currentPage = 1;

        $totalFiltered = $collection->count();
        $pagedItems = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $pagedItems,
            $totalFiltered,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('pegawai.index', [
            'pegawai'   => $pagedItems,
            'paginator' => $paginator,
            'keyword'   => $keyword,
            'sort'      => $sort,
        ]);
    }

    public function create()
    {
        $jabatanList = $this->getJabatanList();
        $unitKerjaList = $this->getUnitKerjaList();

        return view('pegawai.create', compact('jabatanList', 'unitKerjaList'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        $newUuid = (string) Str::uuid();

        // 1. Simpan ke database Supabase PostgreSQL
        try {
            $jabatanRecord = \Illuminate\Support\Facades\DB::table('jabatan')
                ->where('name', $validated['jabatan'])
                ->first();

            $branchRecord = \Illuminate\Support\Facades\DB::table('pegawai')
                ->where('unit_kerja', $validated['unit_kerja'])
                ->whereNotNull('branch_id')
                ->first();

            $insertData = [
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
            ];

            if ($jabatanRecord) {
                $insertData['jabatan_id'] = $jabatanRecord->id;
            }
            if ($branchRecord && $branchRecord->branch_id) {
                $insertData['branch_id'] = $branchRecord->branch_id;
                $insertData['unit_kerja_singkat'] = $branchRecord->unit_kerja_singkat;
            }

            \Illuminate\Support\Facades\DB::table('pegawai')->insert($insertData);
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

        $jabatanList = $this->getJabatanList();
        $unitKerjaList = $this->getUnitKerjaList();

        return view('pegawai.edit', compact('pegawai', 'jabatanList', 'unitKerjaList'));
    }

    public function update(Request $request, int $id)
    {
        $validated = $this->validateData($request, $id);
        $pegawai = $this->find($id);
        abort_if(! $pegawai, 404);

        $dbId = $pegawai['db_id'] ?? null;
        if ($dbId) {
            try {
                $jabatanRecord = \Illuminate\Support\Facades\DB::table('jabatan')
                    ->where('name', $validated['jabatan'])
                    ->first();

                $branchRecord = \Illuminate\Support\Facades\DB::table('pegawai')
                    ->where('unit_kerja', $validated['unit_kerja'])
                    ->whereNotNull('branch_id')
                    ->first();

                $updateData = [
                    'nik' => $validated['nik'],
                    'name' => $validated['nama'],
                    'jabatan' => $validated['jabatan'],
                    'unit_kerja' => $validated['unit_kerja'],
                    'status' => $validated['status_peg'],
                    'no_telp' => $validated['telp'] ?? null,
                    'alamat' => $validated['alamat'] ?? null,
                ];

                if ($jabatanRecord) {
                    $updateData['jabatan_id'] = $jabatanRecord->id;
                }
                if ($branchRecord && $branchRecord->branch_id) {
                    $updateData['branch_id'] = $branchRecord->branch_id;
                    $updateData['unit_kerja_singkat'] = $branchRecord->unit_kerja_singkat;
                }

                \Illuminate\Support\Facades\DB::table('pegawai')->where('id', $dbId)->update($updateData);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('DB pegawai update failed: ' . $e->getMessage());
            }
        }

        static::$memoryCache = null;
        \Illuminate\Support\Facades\Cache::forget('simpeg_all_pegawai_list');
        \Illuminate\Support\Facades\Cache::forget('simpeg_dashboard_stats');
        \Illuminate\Support\Facades\Cache::forget('pegawai_master_cache');

        $refreshed = collect($this->all())->firstWhere('db_id', $dbId);
        $targetId = $refreshed['id'] ?? $id;

        return redirect()->route('pegawai.show', $targetId)->with('success', 'Data pegawai berhasil diperbarui di database.');
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

        $refreshed = collect($this->all())->firstWhere('db_id', $dbId);
        $targetId = $refreshed['id'] ?? $id;

        return redirect()->route('pegawai.show', $targetId)->with('success', 'Pegawai berhasil diangkat menjadi Pegawai Tetap dengan NIK baru: '.$validated['nik_baru']);
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
    public function laporanAnakDiatas21(?Request $request = null)
    {
        $request = $request ?? request();
        $batasUsia = now()->subYears(21);
        $statusFilter = $request->get('status', 'semua');

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
            ->filter(function ($anak) use ($batasUsia, $statusFilter) {
                $tglLahir = $anak['tgl_lahir'] ?? null;
                if (empty($tglLahir) || $tglLahir === '-') return false;

                try {
                    $carbonLahir = \Illuminate\Support\Carbon::parse($tglLahir);
                    // Tampilkan anak yang berusia >= 21 tahun (baik eksak maupun tahun kalender)
                    $isDiatas21 = $carbonLahir->lte($batasUsia) || (now()->year - $carbonLahir->year >= 21);
                    if (! $isDiatas21) {
                        return false;
                    }

                    if ($statusFilter !== 'semua') {
                        $ket = strtolower($anak['keterangan'] ?? '');
                        if ($statusFilter === 'kuliah') {
                            return str_contains($ket, 'kuliah') && ! str_contains($ket, 'tidak');
                        } elseif ($statusFilter === 'tidak-kuliah') {
                            return str_contains($ket, 'tidak');
                        }
                    }

                    return true;
                } catch (\Throwable $e) {
                    return false;
                }
            })
            ->map(function ($anak) {
                try {
                    $carbonLahir = \Illuminate\Support\Carbon::parse($anak['tgl_lahir']);
                    $usia = (int) $carbonLahir->diffInYears(now());
                    if ($usia < 21 && (now()->year - $carbonLahir->year >= 21)) {
                        $usia = now()->year - $carbonLahir->year;
                    }
                    $anak['usia'] = $usia;
                } catch (\Throwable $e) {
                    $anak['usia'] = 0;
                }
                return $anak;
            })
            ->sortBy('nama_pegawai')
            ->values();

        return view('pegawai.laporan-anak', compact('data', 'statusFilter'));
    }

    /**
     * Helper privat untuk mengambil data pegawai per unit kerja dengan profil lengkap,
     * status kepegawaian resmi, dan masa kerja akurat.
     */
    protected function getPegawaiUnitKerjaData(?string $selectedUnit = null): array
    {
        $unitKerjaList = \Illuminate\Support\Facades\DB::table('pegawai')
            ->whereNotNull('unit_kerja')
            ->where('unit_kerja', '!=', '')
            ->select('unit_kerja')
            ->distinct()
            ->orderBy('unit_kerja')
            ->pluck('unit_kerja')
            ->values();

        $query = \Illuminate\Support\Facades\DB::table('pegawai')
            ->where(function ($q) {
                $q->whereNull('status')
                  ->orWhereNotIn(\Illuminate\Support\Facades\DB::raw('UPPER(status)'), ['PN', 'PENSIUN', 'KELUAR']);
            });

        if (!empty($selectedUnit) && $selectedUnit !== 'all') {
            $query->where('unit_kerja', $selectedUnit);
        }

        $rows = $query->orderBy('unit_kerja', 'asc')
            ->orderBy('jabatan', 'asc')
            ->orderBy('nik', 'asc')
            ->get();

        $filtered = $rows->map(function ($p, $idx) {
            $p = (array) $p;
            $nik = trim((string)($p['nik'] ?? ''));
            $rawNik = preg_replace('/\s+/', '', $nik);

            // Format NIK berjarak sesuai standar dokumen kedinasan (contoh: 17 11 252)
            $formattedNik = strlen($rawNik) >= 6
                ? substr($rawNik, 0, 2) . ' ' . substr($rawNik, 2, 2) . ' ' . substr($rawNik, 4)
                : $nik;

            // Hitung Masa Kerja akurat berdasarkan NIK Perumdam Tirta Darma Ayu (YY MM sequence)
            $masaKerja = '-';
            if (strlen($rawNik) >= 4) {
                $yy = (int) substr($rawNik, 0, 2);
                $mm = (int) substr($rawNik, 2, 2);
                $year = ($yy > 50) ? (1900 + $yy) : (2000 + $yy);
                if ($mm >= 1 && $mm <= 12 && $year <= now()->year) {
                    $tglMasuk = \Illuminate\Support\Carbon::create($year, $mm, 1);
                    $diff = $tglMasuk->diff(now());
                    $masaKerja = "{$diff->y} Thn, {$diff->m} Bln";
                }
            } elseif (!empty($p['created_at'])) {
                $diff = \Illuminate\Support\Carbon::parse($p['created_at'])->diff(now());
                $masaKerja = "{$diff->y} Thn, {$diff->m} Bln";
            }

            // Normalisasi Status Pegawai
            $rawStatus = trim((string)($p['status'] ?? 'Pegawai Tetap'));
            $statusLabel = match (strtoupper($rawStatus)) {
                'CP', 'CAPEG'                 => 'Calon Pegawai',
                'PH', 'HONORER'               => 'Pegawai Honorer',
                'PK', 'PKWT', 'KONTRAK', 'TK' => 'PKWT / Kontrak',
                'DI', 'DIREKSI', 'AKTIF'      => 'Direksi / Eksekutif',
                default                       => 'Pegawai Tetap',
            };

            $gelar = trim((string)($p['gelar'] ?? ''));
            $nama = trim((string)($p['name'] ?? 'Pegawai'));
            $namaLengkap = $nama . (!empty($gelar) ? ', ' . $gelar : '');

            $golonganTeks = trim(($p['golongan'] ?? '-') . (!empty($p['golongan_detail']) ? ' (' . $p['golongan_detail'] . ')' : ''));
            if ($golonganTeks === '-' || empty($golonganTeks)) {
                $golonganTeks = 'Staf Pelaksana';
            }

            $p['no'] = $idx + 1;
            $p['formatted_nik'] = $formattedNik;
            $p['nama_lengkap'] = $namaLengkap;
            $p['status_label'] = $statusLabel;
            $p['masa_kerja'] = $masaKerja;
            $p['golongan_teks'] = $golonganTeks;
            $p['unit_kerja'] = $p['unit_kerja'] ?: 'Kantor Pusat';
            $p['jabatan'] = $p['jabatan'] ?: 'Staf';

            return $p;
        });

        // Rekapitulasi Statistik
        $totalPegawai = $filtered->count();
        $totalTetap   = $filtered->where('status_label', 'Pegawai Tetap')->count();
        $totalPkwt    = $filtered->filter(fn($p) => str_contains($p['status_label'], 'Kontrak') || str_contains($p['status_label'], 'PKWT'))->count();
        $totalCapeg   = $filtered->where('status_label', 'Calon Pegawai')->count();
        $totalHonorer = $filtered->where('status_label', 'Pegawai Honorer')->count();
        $totalDireksi = $filtered->where('status_label', 'Direksi / Eksekutif')->count();

        // Rekapitulasi per Cabang / Unit Kerja
        $rekapPerUnit = $filtered->groupBy('unit_kerja')->map(function ($group, $unitName) {
            return [
                'unit_kerja' => $unitName ?: 'Kantor Pusat',
                'total'      => $group->count(),
                'tetap'      => $group->where('status_label', 'Pegawai Tetap')->count(),
                'pkwt'       => $group->filter(fn($p) => str_contains($p['status_label'], 'Kontrak') || str_contains($p['status_label'], 'PKWT'))->count(),
                'capeg'      => $group->where('status_label', 'Calon Pegawai')->count(),
                'honorer'    => $group->where('status_label', 'Pegawai Honorer')->count(),
            ];
        })->values();

        $pejabatTtd = PejabatTtdController::getTtd('gaji');

        return compact(
            'unitKerjaList',
            'filtered',
            'totalPegawai',
            'totalTetap',
            'totalPkwt',
            'totalCapeg',
            'totalHonorer',
            'totalDireksi',
            'rekapPerUnit',
            'pejabatTtd'
        );
    }

    /**
     * Data Pegawai Per Unit Kerja (Web View).
     * Disamakan dan disempurnakan dari daftar_pegawai_unit_kerja.php di sistem lama.
     */
    public function perUnitKerja(Request $request)
    {
        $selected = $request->get('unit_kerja', 'all');
        $data = $this->getPegawaiUnitKerjaData($selected);

        return view('pegawai.per-unit-kerja', array_merge($data, [
            'selected' => $selected,
        ]));
    }

    /**
     * Cetak Fisik Kedinasan Daftar Pegawai per Cabang / Unit Kerja (Format Landscape A4).
     * Sesuai standar operasional PERUMDAM Tirta Darma Ayu untuk Role 1 (SDM) dan Role 7 (Direksi).
     */
    public function cetakPerUnitKerja(Request $request)
    {
        $selected = $request->get('unit_kerja', 'all');
        $data = $this->getPegawaiUnitKerjaData($selected);

        return view('pegawai.cetak-unit-kerja', array_merge($data, [
            'selected' => $selected,
        ]));
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

    public function getJabatanList(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('simpeg_master_jabatan_list', 300, function () {
            try {
                return \Illuminate\Support\Facades\DB::table('jabatan')
                    ->whereNotNull('name')
                    ->where('name', '!=', '')
                    ->orderBy('name', 'asc')
                    ->pluck('name')
                    ->unique()
                    ->values()
                    ->all();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Get jabatan list failed: ' . $e->getMessage());
                return [];
            }
        });
    }

    public function getUnitKerjaList(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('simpeg_master_unit_kerja_list', 300, function () {
            try {
                // Ambil unit_kerja yang ada di tabel pegawai
                $fromPegawai = \Illuminate\Support\Facades\DB::table('pegawai')
                    ->whereNotNull('unit_kerja')
                    ->where('unit_kerja', '!=', '')
                    ->distinct()
                    ->pluck('unit_kerja')
                    ->all();

                // Ambil juga unit dari tabel cabang jika ada yang belum tercatat di pegawai
                $fromCabang = [];
                $cabangs = \Illuminate\Support\Facades\DB::table('cabang')->get();
                foreach ($cabangs as $c) {
                    $nama = ucwords(trim($c->nama ?? ''));
                    if (empty($nama)) {
                        continue;
                    }

                    if ($c->jenis === 'pusat') {
                        $fromCabang[] = 'Kantor Pusat';
                    } elseif ($c->jenis === 'cabang') {
                        $fromCabang[] = 'Cabang Pelayanan ' . $nama;
                    } elseif ($c->jenis === 'unit') {
                        $fromCabang[] = 'Unit Pelayanan ' . $nama;
                    }
                }

                $merged = array_unique(array_merge($fromPegawai, $fromCabang));
                sort($merged, SORT_STRING | SORT_FLAG_CASE);
                return array_values($merged);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Get unit kerja list failed: ' . $e->getMessage());
                return [];
            }
        });
    }
}

