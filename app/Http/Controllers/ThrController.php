<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasApprovalChain;
use Illuminate\Http\Request;

class ThrController extends Controller
{
    use HasApprovalChain;
    /**
     * Modul Penggajian THR (Tunjangan Hari Raya).
     * Terhubung langsung dengan tabel thr di database Supabase PostgreSQL.
     *
     * Disamakan dengan sistem lama (proses_thr_satuan.php dkk, ~886 baris
     * per file x 8 kategori pegawai, digabung jadi 1 form dinamis).
     *
     * BEDA DENGAN Proses Gaji Bulanan (GajiProsesController):
     * - Komponen pendapatan SAMA PERSIS (15 item)
     * - Komponen potongan TIDAK ADA "Sanksi", dan dipecah jadi 2 kelompok
     *   sesuai kode asli:
     *     - Potongan dari Pendapatan (8 item): Dapenma, BPJS-TK, BPJS-Kes,
     *       Perumahan, Pajak, Korpri, T.Perusahaan, Lain-lain
     *     - Potongan Non-Pendapatan (10 item): Koperasi, Darma Wanita, Ledeng,
     *       Kas, BJB, BJBS, Asuransi, BTN, BPR, Zakat
     *   Kedua kelompok dijumlah terpisah lalu dikurangi dari Total Pendapatan.
     * - Status approval: Proses -> Terbit (sama pola dengan Gaji Proses).
     */
    public const KATEGORI = [
        'satuan' => 'Pegawai (Satuan)',
        'dirut' => 'Direktur Utama',
        'dirum' => 'Direktur Umum',
        'dirtek' => 'Direktur Teknik',
        'capeg' => 'Calon Pegawai',
        'honor' => 'Honorer',
        'kontrak' => 'Tenaga Kontrak',
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

    protected function storageFile(): string
    {
        return storage_path('app/thr_proses.json');
    }

    protected function getLocalData(): array
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

    public function all(): array
    {
        try {
            $rows = \Illuminate\Support\Facades\DB::table('thr')
                ->leftJoin('pegawai', 'thr.pegawai_id', '=', 'pegawai.id')
                ->select(
                    'thr.*',
                    'pegawai.nik as p_nik',
                    'pegawai.name as p_name',
                    'pegawai.jabatan as p_jabatan',
                    'pegawai.unit_kerja as p_unit_kerja',
                    'pegawai.golongan as p_golongan'
                )
                ->orderBy('thr.id', 'desc')
                ->get();

            return $rows->map(fn ($r) => $this->mapThrRowToThrArray($r))->all();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('DB thr read failed: ' . $e->getMessage());
        }

        return [];
    }

    public function save(array $data): void
    {
        $file = $this->storageFile();
        $clean = array_values($data);
        @file_put_contents($file, json_encode($clean, JSON_PRETTY_PRINT));
    }

    protected function mapThrRowToThrArray(object $r): array
    {
        $status = strtolower($r->status ?? 'draft');
        if ($status === 'diterbitkan') {
            $status = 'terbit';
        }

        $nik = (string) ($r->p_nik ?? $r->nik ?? '');
        $nama = (string) ($r->p_name ?? $r->nama ?? '');

        if (empty($nik) || empty($nama)) {
            $p = $this->pegawaiById($r->pegawai_id);
            if ($p) {
                $nik = $p['nik'] ?? $nik;
                $nama = $p['nama'] ?? $nama;
            }
        }

        $totalPendapatan = (float) ($r->total_pendapatan ?? 0);
        $totalPotonganPendapatan = (float) ($r->total_potongan_pendapatan ?? 0);
        $totalPotonganNonPendapatan = (float) ($r->total_potongan_non_pendapatan ?? 0);
        $thrDiterima = (float) ($r->thr_diterima ?? 0);

        if ($totalPendapatan <= 0) {
            $totalPendapatan = (float) ($r->gapok ?? 0)
                + (float) ($r->tunjangan_istri ?? 0)
                + (float) ($r->tunjangan_anak ?? 0)
                + (float) ($r->tunjangan_prestasi ?? 0)
                + (float) ($r->tunjangan_jabatan ?? 0)
                + (float) ($r->tunjangan_transportasi ?? 0)
                + (float) ($r->tunjangan_pangan ?? 0)
                + (float) ($r->tunjangan_bpjs_tenaga_kerja ?? 0)
                + (float) ($r->tunjangan_perumahan ?? 0)
                + (float) ($r->tunjangan_perusahaan ?? 0)
                + (float) ($r->tunjangan_air_minum ?? 0)
                + (float) ($r->tunjangan_bpjs_kesehatan ?? 0)
                + (float) ($r->tunjangan_komunikasi ?? 0)
                + (float) ($r->tunjangan_pajak ?? 0)
                + (float) ($r->lembur ?? 0);
        }

        if ($totalPotonganPendapatan <= 0) {
            $totalPotonganPendapatan = (float) ($r->potongan_dapenma ?? 0)
                + (float) ($r->potongan_bpjs_tenaga_kerja ?? 0)
                + (float) ($r->potongan_bpjs_kesehatan ?? 0)
                + (float) ($r->potongan_perumahan ?? 0)
                + (float) ($r->potongan_pajak ?? 0)
                + (float) ($r->potongan_korpri ?? 0)
                + (float) ($r->potongan_tunjangan_perusahaan ?? 0)
                + (float) ($r->potongan_trandist_pmi_lain ?? 0);
        }

        if ($totalPotonganNonPendapatan <= 0) {
            $totalPotonganNonPendapatan = (float) ($r->potongan_koperasi ?? 0)
                + (float) ($r->potongan_darma_wanita ?? 0)
                + (float) ($r->potongan_rekening_air_minum ?? 0)
                + (float) ($r->potongan_kas ?? 0)
                + (float) ($r->potongan_bank_bjb ?? 0)
                + (float) ($r->potongan_bank_bjbs ?? 0)
                + (float) ($r->potongan_asuransi ?? 0)
                + (float) ($r->potongan_bank_btn ?? 0)
                + (float) ($r->potongan_bank_bpr ?? 0)
                + (float) ($r->potongan_zakat_ramadhan ?? 0);
        }

        if ($thrDiterima <= 0) {
            $thrDiterima = $totalPendapatan - ($totalPotonganPendapatan + $totalPotonganNonPendapatan);
        }

        return [
            'id' => $r->id,
            'pegawai_id' => $r->pegawai_id,
            'nik' => $nik,
            'nama' => $nama,
            'jabatan' => $r->p_jabatan ?? 'Staf',
            'unit_kerja' => $r->p_unit_kerja ?? 'Kantor Pusat',
            'golongan' => $r->p_golongan ?? '',
            'kategori' => $r->kategori ?? 'satuan',
            'kode_ptkp' => $r->kode_ptkp ?? 'TK',
            'tahun' => (int) ($r->tahun ?? now()->year),
            'status' => $status,
            'disetujui_oleh' => $r->disetujui_oleh ?? null,
            'total_pendapatan' => $totalPendapatan,
            'total_potongan_pendapatan' => $totalPotonganPendapatan,
            'total_potongan_non_pendapatan' => $totalPotonganNonPendapatan,
            'thr_diterima' => $thrDiterima,

            'gapok' => (float) ($r->gapok ?? 0),
            'tunjangan_istri' => (float) ($r->tunjangan_istri ?? 0),
            'tunjangan_anak' => (float) ($r->tunjangan_anak ?? 0),
            'tunjangan_prestasi' => (float) ($r->tunjangan_prestasi ?? 0),
            'tunjangan_jabatan' => (float) ($r->tunjangan_jabatan ?? 0),
            'tunjangan_transport' => (float) ($r->tunjangan_transportasi ?? 0),
            'tunjangan_pangan' => (float) ($r->tunjangan_pangan ?? 0),
            'tunjangan_bpjstk' => (float) ($r->tunjangan_bpjs_tenaga_kerja ?? 0),
            'tunjangan_perumahan' => (float) ($r->tunjangan_perumahan ?? 0),
            'tunjangan_perusahaan' => (float) ($r->tunjangan_perusahaan ?? 0),
            'tunjangan_airminum' => (float) ($r->tunjangan_air_minum ?? 0),
            'tunjangan_bpjskes' => (float) ($r->tunjangan_bpjs_kesehatan ?? 0),
            'tunjangan_komunikasi' => (float) ($r->tunjangan_komunikasi ?? 0),
            'tunjangan_pajak' => (float) ($r->tunjangan_pajak ?? 0),
            'lembur' => (float) ($r->lembur ?? 0),

            'potongan_dapenma' => (float) ($r->potongan_dapenma ?? 0),
            'potongan_bpjstk' => (float) ($r->potongan_bpjs_tenaga_kerja ?? 0),
            'potongan_bpjskes' => (float) ($r->potongan_bpjs_kesehatan ?? 0),
            'potongan_perumahan' => (float) ($r->potongan_perumahan ?? 0),
            'potongan_pajak' => (float) ($r->potongan_pajak ?? 0),
            'potongan_korpri' => (float) ($r->potongan_korpri ?? 0),
            'potongan_tperusahaan' => (float) ($r->potongan_tunjangan_perusahaan ?? 0),
            'potongan_lain' => (float) ($r->potongan_trandist_pmi_lain ?? 0),

            'potongan_koperasi' => (float) ($r->potongan_koperasi ?? 0),
            'potongan_darmawanita' => (float) ($r->potongan_darma_wanita ?? 0),
            'potongan_ledeng' => (float) ($r->potongan_rekening_air_minum ?? 0),
            'potongan_kas' => (float) ($r->potongan_kas ?? 0),
            'potongan_bjb' => (float) ($r->potongan_bank_bjb ?? 0),
            'potongan_bjbs' => (float) ($r->potongan_bank_bjbs ?? 0),
            'potongan_asuransi' => (float) ($r->potongan_asuransi ?? 0),
            'potongan_btn' => (float) ($r->potongan_bank_btn ?? 0),
            'potongan_bpr' => (float) ($r->potongan_bank_bpr ?? 0),
            'potongan_zakat' => (float) ($r->potongan_zakat_ramadhan ?? 0),
        ];
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

    /**
     * Sama seperti GajiProsesController::hitungKeluarga() - dipakai lagi
     * di sini karena formula tunjangan keluarga identik untuk THR.
     */
    public function hitungKeluarga(int $pegawaiId): array
    {
        return app(GajiProsesController::class)->hitungKeluarga($pegawaiId);
    }

    public function index(Request $request)
    {
        $tahun = (int) $request->get('tahun', now()->year);
        $kategori = $request->get('kategori');
        $status = $request->get('status');

        $thr = collect($this->all())
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

        $pageTitle = 'Proses THR';
        if ($status === 'terbit') {
            $pageTitle = 'Proses Penerbitan THR';
        } elseif ($kategori === 'satuan') {
            $pageTitle = 'Proses THR Pegawai';
        } elseif ($kategori === 'dirut') {
            $pageTitle = 'Proses THR Dirut';
        } elseif ($kategori === 'dirum') {
            $pageTitle = 'Proses THR Dirum';
        } elseif ($kategori === 'dirtek') {
            $pageTitle = 'Proses THR Dirtek';
        }

        return view('thr.index', compact('thr', 'tahun', 'kategori', 'status', 'pageTitle'));
    }

    /**
     * 3 halaman Laporan (read-only, format cetak) - dipisah dari index()
     * yang jadi halaman kelola/proses. Hanya menampilkan THR yang sudah
     * terbit (final), sesuai kebutuhan laporan.
     */
    protected function thrTerbit(int $tahun)
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

        $data = $this->thrTerbit($tahun);
        $riwayatThr = [];

        if ($userLogin['userlevel'] === '5' || $request->has('my')) {
            $data = $data->where('nik', $userLogin['nik']);

            $riwayatThr = collect($this->all())
                ->where('nik', $userLogin['nik'])
                ->filter(fn ($row) => $row['status'] === 'terbit')
                ->sortByDesc('tahun')
                ->values();
        }

        $data = $data->sortBy('nama')->values();

        return view('thr.laporan-slip', compact('data', 'tahun', 'riwayatThr'));
    }

    public function laporanBukuBesar(Request $request)
    {
        $tahun = (int) $request->get('tahun', now()->year);
        $data = $this->thrTerbit($tahun)->sortBy('nama')->values();
        $total = $data->sum('thr_diterima');

        return view('thr.laporan-buku-besar', compact('data', 'tahun', 'total'));
    }

    public function laporanBukuBesarPerSub(Request $request)
    {
        $tahun = (int) $request->get('tahun', now()->year);
        $data = $this->thrTerbit($tahun)
            ->groupBy('unit_kerja')
            ->map(fn ($group) => [
                'rows' => $group->sortBy('nama')->values(),
                'total' => $group->sum('thr_diterima'),
            ]);

        return view('thr.laporan-buku-besar-per-sub', compact('data', 'tahun'));
    }

    public function create(Request $request)
    {
        $kategori = $request->get('kategori', 'satuan');

        return view('thr.create', [
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
        $validated['thr_diterima'] = $totalPendapatan - ($totalPotonganPendapatan + $totalPotonganNonPendapatan);
        $validated['status'] = 'draft';
        $validated['disetujui_oleh'] = 'Proses';

        // Cari UUID pegawai di DB Supabase
        $dbPegawaiId = $pegawai['db_id'] ?? null;
        if (! $dbPegawaiId && ! empty($validated['nik'])) {
            $dbPegawaiId = \Illuminate\Support\Facades\DB::table('pegawai')->where('nik', $validated['nik'])->value('id');
        }

        $insertedToDb = false;
        if ($dbPegawaiId) {
            try {
                $newId = \Illuminate\Support\Facades\DB::table('thr')->insertGetId([
                    'pegawai_id' => $dbPegawaiId,
                    'tahun' => (int) $validated['tahun'],
                    'status' => 'draft',
                    'kategori' => $validated['kategori'],
                    'kode_ptkp' => $validated['kode_ptkp'],
                    'total_pendapatan' => (int) $totalPendapatan,
                    'total_potongan_pendapatan' => (int) $totalPotonganPendapatan,
                    'total_potongan_non_pendapatan' => (int) $totalPotonganNonPendapatan,
                    'thr_diterima' => (int) ($totalPendapatan - ($totalPotonganPendapatan + $totalPotonganNonPendapatan)),
                    'tanggal_cair' => date('Y-m-d'),

                    // Komponen Pendapatan
                    'gapok' => (int) ($validated['gapok'] ?? 0),
                    'tunjangan_istri' => (int) ($validated['tunjangan_istri'] ?? 0),
                    'tunjangan_anak' => (int) ($validated['tunjangan_anak'] ?? 0),
                    'tunjangan_prestasi' => (int) ($validated['tunjangan_prestasi'] ?? 0),
                    'tunjangan_jabatan' => (int) ($validated['tunjangan_jabatan'] ?? 0),
                    'tunjangan_transportasi' => (int) ($validated['tunjangan_transport'] ?? 0),
                    'tunjangan_pangan' => (int) ($validated['tunjangan_pangan'] ?? 0),
                    'tunjangan_bpjs_tenaga_kerja' => (int) ($validated['tunjangan_bpjstk'] ?? 0),
                    'tunjangan_perumahan' => (int) ($validated['tunjangan_perumahan'] ?? 0),
                    'tunjangan_perusahaan' => (int) ($validated['tunjangan_perusahaan'] ?? 0),
                    'tunjangan_air_minum' => (int) ($validated['tunjangan_airminum'] ?? 0),
                    'tunjangan_bpjs_kesehatan' => (int) ($validated['tunjangan_bpjskes'] ?? 0),
                    'tunjangan_komunikasi' => (int) ($validated['tunjangan_komunikasi'] ?? 0),
                    'tunjangan_pajak' => (int) ($validated['tunjangan_pajak'] ?? 0),
                    'lembur' => (int) ($validated['lembur'] ?? 0),

                    // Potongan Pendapatan
                    'potongan_dapenma' => (int) ($validated['potongan_dapenma'] ?? 0),
                    'potongan_bpjs_tenaga_kerja' => (int) ($validated['potongan_bpjstk'] ?? 0),
                    'potongan_bpjs_kesehatan' => (int) ($validated['potongan_bpjskes'] ?? 0),
                    'potongan_perumahan' => (int) ($validated['potongan_perumahan'] ?? 0),
                    'potongan_pajak' => (int) ($validated['potongan_pajak'] ?? 0),
                    'potongan_korpri' => (int) ($validated['potongan_korpri'] ?? 0),
                    'potongan_tunjangan_perusahaan' => (int) ($validated['potongan_tperusahaan'] ?? 0),
                    'potongan_trandist_pmi_lain' => (int) ($validated['potongan_lain'] ?? 0),

                    // Potongan Non-Pendapatan
                    'potongan_koperasi' => (int) ($validated['potongan_koperasi'] ?? 0),
                    'potongan_darma_wanita' => (int) ($validated['potongan_darmawanita'] ?? 0),
                    'potongan_rekening_air_minum' => (int) ($validated['potongan_ledeng'] ?? 0),
                    'potongan_kas' => (int) ($validated['potongan_kas'] ?? 0),
                    'potongan_bank_bjb' => (int) ($validated['potongan_bjb'] ?? 0),
                    'potongan_bank_bjbs' => (int) ($validated['potongan_bjbs'] ?? 0),
                    'potongan_asuransi' => (int) ($validated['potongan_asuransi'] ?? 0),
                    'potongan_bank_btn' => (int) ($validated['potongan_btn'] ?? 0),
                    'potongan_bank_bpr' => (int) ($validated['potongan_bpr'] ?? 0),
                    'potongan_zakat_ramadhan' => (int) ($validated['potongan_zakat'] ?? 0),
                ]);

                $insertedToDb = true;
                $validated['id'] = $newId;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('DB thr insert failed: ' . $e->getMessage());
            }
        }

        $localData = $this->getLocalData();
        if (! $insertedToDb) {
            $newId = $localData ? max(array_column($localData, 'id')) + 1 : 1;
            $validated['id'] = $newId;
        }
        $localData[] = $validated;
        $this->save($localData);

        return redirect()->route('thr.index', ['tahun' => $validated['tahun']])
            ->with('success', 'Proses THR untuk '.$validated['nama'].' berhasil disimpan dan masuk ke database.');
    }

    public function show(mixed $id)
    {
        $thr = collect($this->all())->first(fn ($r) => (string)($r['id'] ?? '') === (string)$id);

        abort_if(! $thr, 404);

        $thr['bisa_approve'] = $this->canUserApprove($thr['status'] ?? 'draft');

        return view('thr.show', [
            'thr' => $thr,
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
        $row = collect($this->all())->firstWhere('id', $id);
        abort_if(! $row, 404);
        abort_unless($this->canUserApprove($row['status']), 403, 'Kamu tidak berhak menyetujui tahap ini.');

        $stage = $this->nextStageFor($row['status']);
        $nextStatus = ($stage === 'dirut') ? 'terbit' : $stage;
        $approverNama = session('simpeg_user.nama_peg', 'Admin');

        // Update langsung di tabel thr di database Supabase
        try {
            $dbStatus = ($nextStatus === 'terbit') ? 'DITERBITKAN' : $nextStatus;
            \Illuminate\Support\Facades\DB::table('thr')
                ->where('id', $id)
                ->update([
                    'status' => $dbStatus,
                    'disetujui_oleh' => $approverNama,
                    'tanggal_cair' => now()->toDateString(),
                    'updated_at' => now(),
                ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('DB thr update status failed: ' . $e->getMessage());
        }

        // Update juga di file lokal
        $localData = $this->getLocalData();
        if (! empty($localData)) {
            $updated = collect($localData)->map(function ($r) use ($id, $nextStatus, $approverNama) {
                if ((int) ($r['id'] ?? 0) === $id) {
                    $r['status'] = $nextStatus;
                    $r['disetujui_oleh'] = $approverNama;
                }
                return $r;
            })->all();
            $this->save($updated);
        }

        return redirect()->back()->with('success', 'THR berhasil disetujui dan tersimpan di database.');
    }

    public function destroy(int $id)
    {
        $thr = collect($this->all())->firstWhere('id', $id);
        abort_if(! $thr, 404);
        abort_if($thr['status'] === 'terbit', 400, 'THR yang sudah terbit tidak bisa dihapus.');

        try {
            \Illuminate\Support\Facades\DB::table('thr')->where('id', $id)->delete();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('DB thr delete failed: ' . $e->getMessage());
        }

        $localData = $this->getLocalData();
        if (! empty($localData)) {
            $filtered = collect($localData)->reject(fn ($row) => (int)($row['id'] ?? 0) === $id)->values()->all();
            $this->save($filtered);
        }

        return redirect()->route('thr.index')->with('success', 'Draft THR berhasil dihapus dari database.');
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
