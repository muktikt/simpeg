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
    protected function getDefaultPegawaiList(): array
    {
        return [
            [
                'id' => 1,
                'nik' => '2000000001',
                'nama' => 'Mukti Kurniawan',
                'jabatan' => 'Staf SDM',
                'unit_kerja' => 'Kantor Pusat',
                'status_peg' => 'PT',
                'tgl_masuk' => '2017-11-25',
                'telp' => '081234567890',
                'alamat' => 'Jl. Merdeka No. 10, Kota A',
                'keluarga' => [
                    ['id' => 1, 'nama' => 'Sri Wahyuni', 'hubungan' => 'Istri/Suami', 'tgl_lahir' => '1992-03-14', 'keterangan' => '-'],
                    ['id' => 2, 'nama' => 'Budi Santoso', 'hubungan' => 'Anak', 'tgl_lahir' => '2002-05-10', 'keterangan' => 'Tidak Kuliah'],
                    ['id' => 3, 'nama' => 'Citra Santoso', 'hubungan' => 'Anak', 'tgl_lahir' => '2015-08-20', 'keterangan' => '-'],
                ],
                'golongan' => [['id' => 1, 'golongan' => 'III/A', 'tmt' => '2020-01-01']],
                'jabatan_riwayat' => [['id' => 1, 'jabatan' => 'Staf SDM', 'unit_kerja' => 'Kantor Pusat', 'tmt' => '2021-06-01']],
                'pendidikan' => [['id' => 1, 'jenjang' => 'S1', 'jurusan' => 'Manajemen', 'institusi' => 'Universitas A', 'tahun_lulus' => '2016']],
                'prestasi' => [['id' => 1, 'judul' => 'Pegawai Teladan', 'keterangan' => 'Penghargaan tahunan unit kerja', 'tanggal' => '2023-12-10']],
            ],
            [
                'id' => 2,
                'nik' => '2000000002',
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
                'id' => 4,
                'nik' => '3000000003',
                'nama' => 'Budi Santoso',
                'jabatan' => 'Staf Unit Produksi Indramayu',
                'unit_kerja' => 'Cabang Indramayu',
                'status_peg' => 'PT',
                'tgl_masuk' => '2020-01-10',
                'telp' => '087727641009',
                'alamat' => 'Blok Panggang, Tegalsembadra',
                'keluarga' => [],
                'golongan' => [],
                'jabatan_riwayat' => [],
                'pendidikan' => [],
                'prestasi' => [],
            ],
            [
                'id' => 5,
                'nik' => '4000000001',
                'nama' => 'Siti Rahmawati',
                'jabatan' => 'Kepala Divisi Administrasi',
                'unit_kerja' => 'Kantor Pusat',
                'status_peg' => 'PT',
                'tgl_masuk' => '2015-05-12',
                'telp' => '081399887766',
                'alamat' => 'Jl. Kartini No. 4, Indramayu',
                'keluarga' => [],
                'golongan' => [],
                'jabatan_riwayat' => [],
                'pendidikan' => [],
                'prestasi' => [],
            ],
            [
                'id' => 6,
                'nik' => '4000000006',
                'nama' => 'Agus Setiawan',
                'jabatan' => 'Kepala Divisi Teknik',
                'unit_kerja' => 'Kantor Pusat',
                'status_peg' => 'PT',
                'tgl_masuk' => '2014-03-01',
                'telp' => '081288776655',
                'alamat' => 'Jl. Pahlawan No. 12, Indramayu',
                'keluarga' => [],
                'golongan' => [],
                'jabatan_riwayat' => [],
                'pendidikan' => [],
                'prestasi' => [],
            ],
            [
                'id' => 7,
                'nik' => '4000000002',
                'nama' => 'Ahmad Fauzi',
                'jabatan' => 'Kepala Satuan Pengawas Internal',
                'unit_kerja' => 'Kantor Pusat',
                'status_peg' => 'PT',
                'tgl_masuk' => '2016-09-20',
                'telp' => '081377665544',
                'alamat' => 'Jl. Pemuda No. 8, Indramayu',
                'keluarga' => [],
                'golongan' => [],
                'jabatan_riwayat' => [],
                'pendidikan' => [],
                'prestasi' => [],
            ],
            [
                'id' => 8,
                'nik' => '4000000003',
                'nama' => 'Dedi Kurniawan',
                'jabatan' => 'Tim Penegak Disiplin Pegawai dan Kode Etik',
                'unit_kerja' => 'Kantor Pusat',
                'status_peg' => 'PT',
                'tgl_masuk' => '2017-02-15',
                'telp' => '081266554433',
                'alamat' => 'Jl. Gajah Mada No. 15, Indramayu',
                'keluarga' => [],
                'golongan' => [],
                'jabatan_riwayat' => [],
                'pendidikan' => [],
                'prestasi' => [],
            ],
            [
                'id' => 9,
                'nik' => '5000000001',
                'nama' => 'H. Dedi Supriadi',
                'jabatan' => 'Direktur Utama',
                'unit_kerja' => 'Kantor Pusat',
                'status_peg' => 'DI',
                'tgl_masuk' => '2012-01-01',
                'telp' => '081122334455',
                'alamat' => 'Jl. Widarasari No. 1, Indramayu',
                'keluarga' => [],
                'golongan' => [],
                'jabatan_riwayat' => [],
                'pendidikan' => [],
                'prestasi' => [],
            ],
            [
                'id' => 10,
                'nik' => '6000000001',
                'nama' => 'Rina Amelia',
                'jabatan' => 'Staf SDM',
                'unit_kerja' => 'Kantor Pusat',
                'status_peg' => 'PT',
                'tgl_masuk' => '2019-07-01',
                'telp' => '081255443322',
                'alamat' => 'Jl. Ahmad Yani No. 50, Indramayu',
                'keluarga' => [],
                'golongan' => [],
                'jabatan_riwayat' => [],
                'pendidikan' => [],
                'prestasi' => [],
            ],
            [
                'id' => 11,
                'nik' => '5000000002',
                'nama' => 'Victoria Usang',
                'jabatan' => 'Direktur Umum',
                'unit_kerja' => 'Kantor Pusat',
                'status_peg' => 'DI',
                'tgl_masuk' => '2019-07-01',
                'telp' => '081255443322',
                'alamat' => 'Jl. Ahmad Yani No. 50, Indramayu',
                'keluarga' => [],
                'golongan' => [],
                'jabatan_riwayat' => [],
                'pendidikan' => [],
                'prestasi' => [],
            ],
            [
                'id' => 12,
                'nik' => '4000000005',
                'nama' => 'Nur Aisyah Lestari',
                'jabatan' => 'Staf Administrasi',
                'unit_kerja' => 'Kantor Pusat',
                'status_peg' => 'PT',
                'tgl_masuk' => '2020-01-01',
                'telp' => '081233445566',
                'alamat' => 'Jl. Sudirman No. 10, Indramayu',
                'keluarga' => [],
                'golongan' => [],
                'jabatan_riwayat' => [],
                'pendidikan' => [],
                'prestasi' => [],
            ],
        ];
    }

    protected function seedIfEmpty(): void
    {
        $defaults = $this->getDefaultPegawaiList();
        $defaultNiks = array_column($defaults, 'nik');

        if (! session()->has('dummy_pegawai')) {
            session()->put('dummy_pegawai', $defaults);
        } else {
            $existing = session('dummy_pegawai', []);
            $obsoleteNiks = ['4000000004', '1800004', '1800005', '1800003', '1711254', '1800001'];
            
            // Remove obsolete NIKs from session
            $filtered = array_values(array_filter($existing, function ($item) use ($obsoleteNiks) {
                return ! in_array($item['nik'] ?? '', $obsoleteNiks, true);
            }));

            $existingNiks = array_column($filtered, 'nik');
            $updated = count($filtered) !== count($existing);

            foreach ($defaults as $def) {
                if (! in_array($def['nik'], $existingNiks, true)) {
                    $filtered[] = $def;
                    $updated = true;
                }
            }

            if ($updated) {
                session()->put('dummy_pegawai', $filtered);
            }
        }
    }

    protected function all(): array
    {
        $this->seedIfEmpty();
        $sessionPegawai = session('dummy_pegawai', []);

        try {
            $supabasePegawai = \App\Services\SupabaseService::get('pegawai');
            if ($supabasePegawai) {
                $sessionNiks = array_column($sessionPegawai, 'nik');
                foreach ($supabasePegawai as $sp) {
                    $nik = $sp['nik'] ?? null;
                    if ($nik && ! in_array($nik, $sessionNiks, true)) {
                        $sessionPegawai[] = [
                            'id' => count($sessionPegawai) + 1,
                            'nik' => (string) $nik,
                            'nama' => $sp['name'] ?? ($sp['nama'] ?? 'Pegawai'),
                            'jabatan' => $sp['jabatan'] ?? 'Staf',
                            'unit_kerja' => $sp['unit_kerja'] ?? 'Kantor Pusat',
                            'status_peg' => 'PT',
                            'tgl_masuk' => date('Y-m-d'),
                            'telp' => '-',
                            'alamat' => '-',
                            'keluarga' => [],
                            'golongan' => [],
                            'jabatan_riwayat' => [],
                            'pendidikan' => [],
                            'prestasi' => [],
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fallback to session
        }

        return $sessionPegawai;
    }

    protected function save(array $data): void
    {
        session()->put('dummy_pegawai', $data);
    }

    protected function find(int $id): ?array
    {
        $pegawai = collect($this->all())->firstWhere('id', $id);
        if ($pegawai) {
            if (! isset($pegawai['surat_kerja'])) {
                $pegawai['surat_kerja'] = [
                    'nomor' => 'SK/SDM/2024/001',
                    'judul' => 'Surat Keputusan Pengangkatan Pegawai Tetap',
                    'tgl_terbit' => '2024-01-15',
                    'file_name' => 'SK_Pengangkatan_Pegawai.pdf',
                    'file_url' => '#',
                ];
            }
            if (! isset($pegawai['surat_diklat'])) {
                $pegawai['surat_diklat'] = [
                    'nomor' => 'STP/SDM/2024/088',
                    'judul' => 'Sertifikat Diklat & Pelatihan Manajemen Kepegawaian',
                    'tgl_terbit' => '2024-05-20',
                    'file_name' => 'Sertifikat_Diklat_SDM.pdf',
                    'file_url' => '#',
                ];
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

        // Auto-sync ke user-akses agar bisa langsung di-set role/akses di Web
        $userAkses = session('dummy_userakses', []);
        $alreadyInUa = collect($userAkses)->contains('username', $validated['nik']);
        if (! $alreadyInUa) {
            $maxUaId = $userAkses ? max(array_column($userAkses, 'id')) + 1 : 1;
            $userAkses[] = [
                'id' => $maxUaId,
                'username' => $validated['nik'],
                'password' => 'password',
                'nama' => $validated['nama'],
                'userlevel' => '5',
            ];
            session()->put('dummy_userakses', $userAkses);
        }

        return redirect()->route('pegawai.index')->with('success', 'Data pegawai "'.$validated['nama'].'" berhasil ditambahkan.');
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

        $data = collect($this->all())->map(function ($p) use ($id, $validated) {
            if ($p['id'] === $id) {
                $p['nik'] = $validated['nik_baru'];
                $p['status_peg'] = 'PT';
            }

            return $p;
        })->all();

        $this->save($data);

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

        $data = collect($this->all())
            ->flatMap(function ($p) {
                return collect($p['keluarga'] ?? [])
                    ->where('hubungan', 'Anak')
                    ->map(fn ($anak) => array_merge($anak, [
                        'nik_pegawai' => $p['nik'],
                        'nama_pegawai' => $p['nama'],
                    ]));
            })
            ->filter(fn ($anak) => ($anak['keterangan'] ?? '-') === 'Tidak Kuliah'
                && \Illuminate\Support\Carbon::parse($anak['tgl_lahir'])->lte($batasUsia))
            ->map(function ($anak) use ($batasUsia) {
                $anak['usia'] = (int) \Illuminate\Support\Carbon::parse($anak['tgl_lahir'])->diffInYears(now());

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
}
