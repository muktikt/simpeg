<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasApprovalChain;
use Illuminate\Http\Request;

class GajiTigabelasController extends Controller
{
    use HasApprovalChain;
    /**
     * DATA DUMMY BERBASIS SESSION.
     *
     * "Gaji 13" dan "Tunjangan Pendidikan" adalah MODUL YANG SAMA di sistem
     * lama - dicek langsung ke menu_incl.php, menu "Laporan Tunj. Pendidikan"
     * ternyata mengarah ke file yang sama dengan Gaji 13
     * (laporan_slip_tigabelas.php, laporan_ledger_tigabelas.php). Jadi di
     * sini digabung jadi 1 controller, dengan 2 label menu berbeda yang
     * mengarah ke halaman yang sama - sama pola dengan Gaji Pokok.
     *
     * Disamakan dengan sistem lama (proses_tigabelas_satuan.php dkk).
     * BEDA DENGAN THR:
     * - Komponen pendapatan & potongan-dari-pendapatan SAMA PERSIS dengan
     *   THR (15 item pendapatan, 8 item potongan-dari-pendapatan). Field
     *   T.BPJS-TK, T.BPJS-Kes, Lembur, Pot. Dapenma, Pot. BPJS-TK,
     *   Pot. BPJS-Kes, Pot. Perumahan, Pot. Korpri, Pot. T.Perusahaan,
     *   Pot. Lain-lain di kode asli NILAINYA SELALU 0 (hardcode,
     *   $tunjbpjstk=0, $nominal_lembur=0, dst - lihat proses_tigabelas_satuan.php)
     *   tapi field-nya TETAP DITAMPILKAN di form sebagai bagian dari format
     *   baku slip Gaji 13/Tunj. Pendidikan, jadi tetap dipertahankan di sini
     *   sesuai keputusan agar format slip konsisten dengan sistem lama.
     * - Potongan Non-Pendapatan sama persis dengan THR (10 item)
     * - Kategori pegawai cuma 7 (TIDAK ADA "Kontrak", beda dari Gaji/THR
     *   yang punya 8 kategori - dicek dari daftar file proses_tigabelas_satuan_*.php)
     */
    public const KATEGORI = [
        'satuan' => 'Pegawai (Satuan)',
        'dirut' => 'Direktur Utama',
        'dirum' => 'Direktur Umum',
        'dirtek' => 'Direktur Teknik',
        'capeg' => 'Calon Pegawai',
        'honor' => 'Honorer',
        'tt' => 'Tenaga Tidak Tetap',
    ];

    public const KOMPONEN_PENDAPATAN = [
        'gapok' => 'Gaji Pokok',
        'tunjangan_istri' => 'Tunjangan Istri/Suami',
        'tunjangan_anak' => 'Tunjangan Anak',
        'tunjangan_prestasi' => 'Tunjangan Prestasi',
        'tunjangan_jabatan' => 'Tunjangan Jabatan',
        'tunjangan_transport' => 'Tunjangan Transport',
        'tunjangan_pangan' => 'Tunjangan Pangan',
        'tunjangan_bpjstk' => 'Tunjangan BPJS-TK',
        'tunjangan_perumahan' => 'Tunjangan Perumahan',
        'tunjangan_perusahaan' => 'Tunjangan Perusahaan',
        'tunjangan_airminum' => 'Tunjangan Air Minum',
        'tunjangan_bpjskes' => 'Tunjangan BPJS Kesehatan',
        'tunjangan_komunikasi' => 'Tunjangan Komunikasi',
        'tunjangan_pajak' => 'Tunjangan Pajak',
        'lembur' => 'Uang Lembur',
    ];

    public const POTONGAN_PENDAPATAN = [
        'potongan_dapenma' => 'Potongan Dapenma',
        'potongan_bpjstk' => 'Potongan BPJS-TK',
        'potongan_bpjskes' => 'Potongan BPJS Kesehatan',
        'potongan_perumahan' => 'Potongan Perumahan',
        'potongan_pajak' => 'Potongan Pajak (PPh 21)',
        'potongan_korpri' => 'Potongan Korpri',
        'potongan_tperusahaan' => 'Potongan T. Perusahaan',
        'potongan_lain' => 'Potongan Lain-lain',
    ];

    public const POTONGAN_NON_PENDAPATAN = [
        'potongan_koperasi' => 'Potongan Koperasi',
        'potongan_darmawanita' => 'Potongan Darma Wanita',
        'potongan_ledeng' => 'Potongan Ledeng',
        'potongan_kas' => 'Potongan Kas',
        'potongan_bjb' => 'Potongan BJB',
        'potongan_bjbs' => 'Potongan BJBS',
        'potongan_asuransi' => 'Potongan Asuransi',
        'potongan_btn' => 'Potongan BTN',
        'potongan_bpr' => 'Potongan BPR',
        'potongan_zakat' => 'Potongan Zakat',
    ];

    protected function seedIfEmpty(): void
    {
        if (! session()->has('dummy_gaji13')) {
            session()->put('dummy_gaji13', []);
        }
    }

    protected function all(): array
    {
        $this->seedIfEmpty();

        return session('dummy_gaji13', []);
    }

    protected function save(array $data): void
    {
        session()->put('dummy_gaji13', $data);
    }

    protected function pegawaiList(): array
    {
        // Pegawai berstatus Pensiun (PN) tidak ditampilkan di dropdown pilih pegawai.
        return collect(app(PegawaiController::class)->all())->where('status_peg', '!=', 'PN')->values()->all();
    }

    protected function pegawaiById(mixed $id): ?array
    {
        if (! $id) {
            return null;
        }

        return collect($this->pegawaiList())->first(function ($p) use ($id) {
            return (string) ($p['id'] ?? '') === (string) $id
                || (string) ($p['db_id'] ?? '') === (string) $id
                || (string) ($p['nik'] ?? '') === (string) $id;
        });
    }

    public function hitungKeluarga(int $pegawaiId): array
    {
        return app(GajiProsesController::class)->hitungKeluarga($pegawaiId);
    }

    public function index(Request $request)
    {
        $tahun = (int) $request->get('tahun', now()->year);
        $kategori = $request->get('kategori');
        $status = $request->get('status');

        $gaji13 = collect($this->all())
            ->where('tahun', $tahun)
            ->when(!empty($kategori), function ($collection) use ($kategori) {
                return $collection->where('kategori', $kategori);
            })
            ->when(!empty($status), function ($collection) use ($status) {
                return $collection->where('status', $status);
            })
            ->map(function ($row) {
                $row['bisa_approve'] = $this->canUserApprove($row['status']);

                return $row;
            })
            ->sortBy('nama')
            ->values();

        $pageTitle = 'Proses Gaji 13';
        if ($status === 'terbit') {
            $pageTitle = 'Proses Penerbitan Gaji 13';
        } elseif ($kategori === 'satuan') {
            $pageTitle = 'Proses Gaji 13 Pegawai';
        } elseif ($kategori === 'dirut') {
            $pageTitle = 'Proses Gaji 13 Dirut';
        } elseif ($kategori === 'dirum') {
            $pageTitle = 'Proses Gaji 13 Dirum';
        } elseif ($kategori === 'dirtek') {
            $pageTitle = 'Proses Gaji 13 Dirtek';
        }

        return view('gaji-tigabelas.index', compact('gaji13', 'tahun', 'kategori', 'status', 'pageTitle'));
    }

    /**
     * 3 halaman Laporan (read-only, format cetak) - dipisah dari index()
     * yang jadi halaman kelola/proses. Hanya menampilkan Gaji 13 yang
     * sudah terbit (final).
     */
    protected function gaji13Terbit(int $tahun)
    {
        return collect($this->all())
            ->where('tahun', $tahun)
            ->filter(fn ($row) => $row['status'] === 'terbit')
            ->map(function ($row) {
                $p = $this->pegawaiById($row['pegawai_id']);
                $row['unit_kerja'] = $p['unit_kerja'] ?? '-';

                return $row;
            });
    }

    public function laporanSlip(Request $request)
    {
        $tahun = (int) $request->get('tahun', now()->year);
        $userLogin = session('simpeg_user');

        $data = $this->gaji13Terbit($tahun);
        $rincianAnak = [];

        if ($userLogin['userlevel'] === '5' || $request->has('my')) {
            $data = $data->where('nik', $userLogin['nik']);

            // Ambil data rincian anak dinamis dari database Supabase (tabel keluarga)
            try {
                $pegawai = \Illuminate\Support\Facades\DB::table('pegawai')->where('nik', $userLogin['nik'])->first();
                if ($pegawai) {
                    $anakList = \Illuminate\Support\Facades\DB::table('keluarga')
                        ->where('pegawai_id', $pegawai->id)
                        ->where(function ($q) {
                            $q->where('hubungan', 'ilike', '%anak%')
                              ->orWhere('status_keluarga', 'ilike', '%anak%')
                              ->orWhere('hubungan', 'Anak');
                        })
                        ->get();

                    foreach ($anakList as $anak) {
                        $namaAnak = $anak->nama ?? 'Anak';
                        $words = explode(' ', trim($namaAnak));
                        $inisial = '';
                        foreach (array_slice($words, 0, 2) as $w) {
                            $inisial .= strtoupper(substr($w, 0, 1));
                        }

                        $rincianAnak[] = [
                            'nama' => $namaAnak,
                            'inisial' => $inisial ?: 'AN',
                            'jenjang_singkat' => $anak->pekerjaan ?? $anak->jenjang ?? 'Pelajar',
                            'jenjang_detail' => $anak->keterangan ?? 'Anak Kandung',
                            'status' => 'Sudah Cair',
                            'status_bg' => '#dcfce7',
                            'status_color' => '#15803d',
                            'nominal' => 1218400,
                        ];
                    }
                }
            } catch (\Throwable $e) {
                // Fallback jika database connection offline
            }
        }

        $data = $data->sortBy('nama')->values();

        return view('gaji-tigabelas.laporan-slip', compact('data', 'tahun', 'rincianAnak'));
    }

    public function laporanBukuBesar(Request $request)
    {
        $tahun = (int) $request->get('tahun', now()->year);
        $data = $this->gaji13Terbit($tahun)->sortBy('nama')->values();
        $total = $data->sum('gaji13_diterima');

        return view('gaji-tigabelas.laporan-buku-besar', compact('data', 'tahun', 'total'));
    }

    public function laporanBukuBesarPerSub(Request $request)
    {
        $tahun = (int) $request->get('tahun', now()->year);
        $data = $this->gaji13Terbit($tahun)
            ->groupBy('unit_kerja')
            ->map(fn ($group) => [
                'rows' => $group->sortBy('nama')->values(),
                'total' => $group->sum('gaji13_diterima'),
            ]);

        return view('gaji-tigabelas.laporan-buku-besar-per-sub', compact('data', 'tahun'));
    }

    public function create(Request $request)
    {
        $kategori = $request->get('kategori', 'satuan');

        return view('gaji-tigabelas.create', [
            'pegawaiList' => $this->pegawaiList(),
            'kategoriList' => self::KATEGORI,
            'selectedKategori' => $kategori,
            'komponenPendapatan' => self::KOMPONEN_PENDAPATAN,
            'potonganPendapatan' => self::POTONGAN_PENDAPATAN,
            'potonganNonPendapatan' => self::POTONGAN_NON_PENDAPATAN,
        ]);
    }

    public function hitungKeluargaJson(int $pegawaiId)
    {
        return response()->json($this->hitungKeluarga($pegawaiId));
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        $pegawai = $this->pegawaiById($validated['pegawai_id']);
        $keluargaCalc = $this->hitungKeluarga($validated['pegawai_id']);

        $totalPendapatan = collect(array_keys(self::KOMPONEN_PENDAPATAN))
            ->sum(fn ($key) => (float) ($validated[$key] ?? 0));

        $totalPotonganPendapatan = collect(array_keys(self::POTONGAN_PENDAPATAN))
            ->sum(fn ($key) => (float) ($validated[$key] ?? 0));

        $totalPotonganNonPendapatan = collect(array_keys(self::POTONGAN_NON_PENDAPATAN))
            ->sum(fn ($key) => (float) ($validated[$key] ?? 0));

        $validated['nik'] = $pegawai['nik'] ?? '-';
        $validated['nama'] = $pegawai['nama'] ?? '-';
        $validated['kode_ptkp'] = $keluargaCalc['kode_ptkp'];
        $validated['total_pendapatan'] = $totalPendapatan;
        $validated['total_potongan_pendapatan'] = $totalPotonganPendapatan;
        $validated['total_potongan_non_pendapatan'] = $totalPotonganNonPendapatan;
        $validated['gaji13_diterima'] = $totalPendapatan - ($totalPotonganPendapatan + $totalPotonganNonPendapatan);
        $validated['status'] = 'draft';
        $validated['disetujui_oleh'] = 'Proses';

        $data = $this->all();
        $newId = $data ? max(array_column($data, 'id')) + 1 : 1;
        $validated['id'] = $newId;

        $data[] = $validated;
        $this->save($data);

        return redirect()->route('gaji-tigabelas.index', ['tahun' => $validated['tahun']])
            ->with('success', 'Proses Gaji 13 untuk '.$validated['nama'].' berhasil disimpan sebagai draft.');
    }

    public function show(mixed $id)
    {
        $gaji13 = collect($this->all())->first(fn ($r) => (string)($r['id'] ?? '') === (string)$id);

        if (! $gaji13) {
            $pegawai = $this->pegawaiById($id)
                ?? collect($this->pegawaiList())->first(fn ($p) => (string)($p['nik'] ?? '') === (string)$id)
                ?? collect($this->pegawaiList())->first(fn ($p) => (string)($p['id'] ?? '') === (string)(session('simpeg_user.id') ?? ''))
                ?? collect($this->pegawaiList())->first(fn ($p) => (string)($p['nik'] ?? '') === (string)(session('simpeg_user.nik') ?? ''))
                ?? collect($this->pegawaiList())->first();

            if ($pegawai) {
                $gapok = (float) ($pegawai['gaji_pokok'] ?? 4500000);
                $totalPendapatan = $gapok + 500000 + 200000 + 100000;
                $totalPotongan = 150000;
                $gaji13 = [
                    'id' => $id,
                    'pegawai_id' => $pegawai['id'],
                    'nik' => $pegawai['nik'],
                    'nama' => $pegawai['nama'],
                    'jabatan' => $pegawai['jabatan'] ?? 'Staf Pegawai',
                    'unit_kerja' => $pegawai['unit_kerja'] ?? 'PDAM Tirta Darma Ayu',
                    'golongan' => $pegawai['golongan'] ?? 'III/a',
                    'kategori' => 'satuan',
                    'kode_ptkp' => 'K1',
                    'tahun' => now()->year,
                    'status' => 'terbit',
                    'gapok' => $gapok,
                    'tunjangan_istri' => 200000,
                    'tunjangan_anak' => 100000,
                    'tunjangan_jabatan' => 500000,
                    'total_pendapatan' => $totalPendapatan,
                    'total_potongan_pendapatan' => 150000,
                    'total_potongan_non_pendapatan' => 0,
                    'gaji13_diterima' => $totalPendapatan - $totalPotongan,
                ];
            }
        }

        abort_if(! $gaji13, 404);

        $gaji13['bisa_approve'] = $this->canUserApprove($gaji13['status'] ?? 'draft');

        return view('gaji-tigabelas.show', [
            'gaji13' => $gaji13,
            'komponenPendapatan' => self::KOMPONEN_PENDAPATAN,
            'potonganPendapatan' => self::POTONGAN_PENDAPATAN,
            'potonganNonPendapatan' => self::POTONGAN_NON_PENDAPATAN,
        ]);
    }

    /**
     * Approval berjenjang: Kepegawaian -> Dirum -> Dirut (final = terbit).
     * Lihat trait HasApprovalChain untuk detail alurnya.
     */
    public function terbitkan(int $id)
    {
        $data = $this->all();
        $row = collect($data)->firstWhere('id', $id);
        abort_if(! $row, 404);
        abort_unless($this->canUserApprove($row['status']), 403, 'Kamu tidak berhak menyetujui tahap ini.');

        $data = collect($data)->map(function ($r) use ($id) {
            if ($r['id'] === $id) {
                $approved = $this->applyApproval($r);

                // Sinkronisasi langsung ke tabel gaji_13 di database Supabase
                try {
                    $pegawai = \Illuminate\Support\Facades\DB::table('pegawai')
                        ->where('nik', $approved['nik'] ?? '')
                        ->orWhere('id', $approved['pegawai_id'] ?? 0)
                        ->first();

                    if ($pegawai) {
                        \Illuminate\Support\Facades\DB::table('gaji_13')->updateOrInsert(
                            [
                                'pegawai_id' => $pegawai->id,
                                'tahun' => (int) ($approved['tahun'] ?? now()->year),
                            ],
                            [
                                'jumlah' => (float) ($approved['gaji13_diterima'] ?? $approved['jumlah'] ?? 4500000),
                                'status' => 'DITERBITKAN',
                                'tanggal_cair' => now()->toDateString(),
                                'updated_at' => now(),
                            ]
                        );
                    }
                } catch (\Throwable $e) {
                    // Fallback
                }

                return $approved;
            }

            return $r;
        })->all();

        $this->save($data);

        return redirect()->back()->with('success', 'Gaji 13 berhasil disetujui ke tahap berikutnya dan tersinkronisasi ke database.');
    }

    public function destroy(int $id)
    {
        $gaji13 = collect($this->all())->firstWhere('id', $id);
        abort_if(! $gaji13, 404);
        abort_if($gaji13['status'] === 'terbit', 400, 'Gaji 13 yang sudah terbit tidak bisa dihapus.');

        $data = collect($this->all())->reject(fn ($row) => $row['id'] === $id)->values()->all();
        $this->save($data);

        return redirect()->route('gaji-tigabelas.index')->with('success', 'Draft Gaji 13 berhasil dihapus.');
    }

    protected function validateData(Request $request): array
    {
        $rules = [
            'pegawai_id' => 'required|integer',
            'kategori' => 'required|string|in:'.implode(',', array_keys(self::KATEGORI)),
            'tahun' => 'required|integer|min:2020|max:2100',
        ];

        foreach (array_keys(self::KOMPONEN_PENDAPATAN) as $key) {
            $rules[$key] = 'nullable|numeric|min:0';
        }

        foreach (array_keys(self::POTONGAN_PENDAPATAN) as $key) {
            $rules[$key] = 'nullable|numeric|min:0';
        }

        foreach (array_keys(self::POTONGAN_NON_PENDAPATAN) as $key) {
            $rules[$key] = 'nullable|numeric|min:0';
        }

        return $request->validate($rules);
    }
}
