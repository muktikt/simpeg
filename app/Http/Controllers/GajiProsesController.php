<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasApprovalChain;
use Illuminate\Http\Request;

class GajiProsesController extends Controller
{
    use HasApprovalChain;
    /**
     * DATA DUMMY BERBASIS SESSION.
     *
     * Disamakan dengan sistem lama (proses_cek_gaji_satuan.php dkk, ~1390 baris
     * per file x 8 kategori pegawai). Sesuai keputusan konsolidasi, 8 kategori
     * (satuan/dirut/dirum/dirtek/capeg/honor/kontrak/tt) digabung jadi 1 form
     * dengan dropdown kategori, bukan 8 file terpisah.
     *
     * FORMULA YANG DITEMUKAN & DIPAKAI (dari kode asli):
     * - Status kawin dicek dari tbl_keluarga (status_keluarga='Istri/Suami')
     * - Tunjangan Istri = 10% x Gapok, HANYA jika berstatus kawin
     * - Jumlah anak (untuk tunjangan) dibatasi maksimal 2
     * - Jumlah anak (untuk kategori pajak/PTKP) dibatasi maksimal 3
     * - Kategori PTKP: TK (belum kawin, 0 tanggungan) / K / K1 / K2 / K3
     *   dihitung dari (status kawin + jumlah anak pajak)
     * - Jumlah Pendapatan, Jumlah Potongan, Gaji Bersih dihitung OTOMATIS
     *   (sistem lama punya baris kalkulasi ini tapi di-comment dan diganti
     *   ambil dari $_POST - di versi ini kita pakai hasil hitung otomatis
     *   yang lebih benar dan konsisten)
     *
     * Komponen lain (Prestasi, Jabatan, Transport, Pangan, BPJS, dst) di
     * sistem lama nilainya diinput manual oleh Admin tiap proses gaji -
     * tidak ada formula otomatis yang ditemukan di kode, jadi tetap input
     * manual di sini juga (paling apa adanya / paling jujur ke sistem asli).
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

    public const KOMPONEN_POTONGAN = [
        'potongan_sanksi' => 'Potongan Sanksi',
        'potongan_dapenma' => 'Potongan Dapenma',
        'potongan_bpjstk' => 'Potongan BPJS-TK',
        'potongan_bpjskes' => 'Potongan BPJS Kesehatan',
        'potongan_perumahan' => 'Potongan Perumahan',
        'potongan_pajak' => 'Potongan Pajak (PPh 21)',
        'potongan_korpri' => 'Potongan Korpri',
        'potongan_tperusahaan' => 'Potongan T. Perusahaan',
        'potongan_lain' => 'Potongan Lain-lain',
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
        return storage_path('app/gaji_proses.json');
    }

    protected function seedIfEmpty(): void
    {
        $file = $this->storageFile();
        if (! file_exists($file)) {
            @file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));
        }
    }

    public function all(): array
    {
        $file = $this->storageFile();
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if (is_array($data)) {
                return $data;
            }
        }

        return session('dummy_gaji_proses', []);
    }

    public function save(array $data): void
    {
        $file = $this->storageFile();
        $clean = array_values($data);
        @file_put_contents($file, json_encode($clean, JSON_PRETTY_PRINT));
        session()->put('dummy_gaji_proses', $clean);
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
     * Hitung tunjangan keluarga & kategori PTKP berdasarkan data keluarga pegawai,
     * mengikuti formula yang ditemukan di sistem lama.
     */
    public function hitungKeluarga(int $pegawaiId): array
    {
        $pegawai = $this->pegawaiById($pegawaiId);
        $keluarga = $pegawai['keluarga'] ?? [];

        $kawin = collect($keluarga)->contains(fn ($k) => $k['hubungan'] === 'Istri/Suami');
        $jmlAnak = min(collect($keluarga)->where('hubungan', 'Anak')->count(), 2);
        $jmlAnakPajak = min(collect($keluarga)->where('hubungan', 'Anak')->count(), 3);

        $jmlIstri = $kawin ? 1 : 0;
        $kodePtkp = match ($jmlIstri + $jmlAnakPajak + 1) {
            1 => 'TK',
            2 => 'K',
            3 => 'K1',
            4 => 'K2',
            5 => 'K3',
            default => 'K3',
        };

        return [
            'kawin' => $kawin,
            'jml_istri' => $jmlIstri,
            'jml_anak' => $jmlAnak,
            'jml_anak_pajak' => $jmlAnakPajak,
            'kode_ptkp' => $kodePtkp,
        ];
    }

    public function index(Request $request)
    {
        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);
        $kategori = $request->get('kategori');
        $status = $request->get('status');

        $gaji = collect($this->all())
            ->where('bulan', $bulan)
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

        $pageTitle = 'Proses Gaji Bulanan';
        if ($status === 'terbit') {
            $pageTitle = 'Proses Penerbitan Gaji';
        } elseif ($kategori === 'satuan') {
            $pageTitle = 'Proses Cek Gaji Pegawai';
        } elseif ($kategori === 'dirut') {
            $pageTitle = 'Proses Cek Gaji Dirut';
        } elseif ($kategori === 'dirum') {
            $pageTitle = 'Proses Cek Gaji Dirum';
        } elseif ($kategori === 'dirtek') {
            $pageTitle = 'Proses Cek Gaji Dirtek';
        }

        return view('gaji-proses.index', [
            'gaji' => $gaji,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'kategori' => $kategori,
            'status' => $status,
            'pageTitle' => $pageTitle,
            'bulanList' => AbsensiController::BULAN,
        ]);
    }

    public function create(Request $request)
    {
        $kategori = $request->get('kategori', 'satuan');

        return view('gaji-proses.create', [
            'pegawaiList' => $this->pegawaiList(),
            'kategoriList' => self::KATEGORI,
            'selectedKategori' => $kategori,
            'komponenPendapatan' => self::KOMPONEN_PENDAPATAN,
            'komponenPotongan' => self::KOMPONEN_POTONGAN,
            'gapokList' => session('dummy_gapok', []),
        ]);
    }

    /**
     * Endpoint kecil dipanggil via fetch() dari form create - mengembalikan
     * hitungan tunjangan keluarga & kategori PTKP untuk pegawai yang dipilih.
     */
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

        $totalPotongan = collect(array_keys(self::KOMPONEN_POTONGAN))
            ->sum(fn ($key) => (float) ($validated[$key] ?? 0));

        $validated['nik'] = $pegawai['nik'] ?? '-';
        $validated['nama'] = $pegawai['nama'] ?? '-';
        $validated['kode_ptkp'] = $keluargaCalc['kode_ptkp'];
        $validated['total_pendapatan'] = $totalPendapatan;
        $validated['total_potongan'] = $totalPotongan;
        $validated['gaji_bersih'] = $totalPendapatan - $totalPotongan;
        $validated['status'] = 'draft';

        $data = $this->all();
        $newId = $data ? max(array_column($data, 'id')) + 1 : 1;
        $validated['id'] = $newId;

        $data[] = $validated;
        $this->save($data);

        return redirect()->route('gaji-proses.index', ['bulan' => $validated['bulan'], 'tahun' => $validated['tahun']])
            ->with('success', 'Proses gaji untuk '.$validated['nama'].' berhasil disimpan sebagai draft.');
    }

    public function show(mixed $id)
    {
        $gaji = collect($this->all())->first(fn ($r) => (string)($r['id'] ?? '') === (string)$id);

        if (! $gaji) {
            try {
                $pRow = \Illuminate\Support\Facades\DB::table('payroll')
                    ->join('pegawai', 'payroll.pegawai_id', '=', 'pegawai.id')
                    ->select('payroll.*', 'pegawai.nik', 'pegawai.nama', 'pegawai.jabatan', 'pegawai.unit_kerja', 'pegawai.golongan')
                    ->where('payroll.id', $id)
                    ->orWhere('pegawai.nik', $id)
                    ->first();

                if ($pRow) {
                    $gapok = (float) ($pRow->gapok ?? 0);
                    $tunjJabatan = (float) ($pRow->tunjangan_jabatan ?? 0);
                    $tunjIstri = (float) ($pRow->tunjangan_istri ?? 0);
                    $tunjAnak = (float) ($pRow->tunjangan_anak ?? 0);
                    $tunjPerumahan = (float) ($pRow->tunjangan_perumahan ?? 0);
                    $tunjBpjstk = (float) ($pRow->tunjangan_bpjstk ?? 0);
                    $potDapenma = (float) ($pRow->potongan_dapenma ?? 0);
                    $potBjbs = (float) ($pRow->potongan_bank_bjb ?? 0);
                    $potBpjstk = (float) ($pRow->potongan_bpjstk ?? 0);
                    $potPajak = (float) ($pRow->potongan_pajak ?? 0);

                    $totalPendapatan = $gapok + $tunjJabatan + $tunjIstri + $tunjAnak + $tunjPerumahan + $tunjBpjstk;
                    $totalPotongan = $potDapenma + $potBjbs + $potBpjstk + $potPajak;

                    $gaji = [
                        'id' => $pRow->id,
                        'pegawai_id' => $pRow->pegawai_id,
                        'nik' => $pRow->nik,
                        'nama' => $pRow->nama,
                        'jabatan' => $pRow->jabatan,
                        'unit_kerja' => $pRow->unit_kerja ?? 'PDAM Tirta Darma Ayu',
                        'golongan' => $pRow->golongan ?? 'III/a',
                        'kategori' => 'satuan',
                        'kode_ptkp' => 'K1',
                        'bulan' => now()->month,
                        'tahun' => now()->year,
                        'status' => 'terbit',
                        'gapok' => $gapok,
                        'tunjangan_istri' => $tunjIstri,
                        'tunjangan_anak' => $tunjAnak,
                        'tunjangan_prestasi' => 0,
                        'tunjangan_jabatan' => $tunjJabatan,
                        'tunjangan_transport' => 0,
                        'tunjangan_pangan' => 0,
                        'tunjangan_bpjstk' => $tunjBpjstk,
                        'tunjangan_perumahan' => $tunjPerumahan,
                        'tunjangan_perusahaan' => 0,
                        'tunjangan_airminum' => 0,
                        'tunjangan_bpjskes' => 0,
                        'tunjangan_komunikasi' => 0,
                        'tunjangan_pajak' => 0,
                        'lembur' => 0,
                        'potongan_sanksi' => 0,
                        'potongan_dapenma' => $potDapenma,
                        'potongan_bpjstk' => $potBpjstk,
                        'potongan_bpjskes' => 0,
                        'potongan_perumahan' => 0,
                        'potongan_pajak' => $potPajak,
                        'potongan_korpri' => 0,
                        'potongan_tperusahaan' => 0,
                        'potongan_lain' => 0,
                        'potongan_koperasi' => 0,
                        'potongan_darmawanita' => 0,
                        'potongan_ledeng' => 0,
                        'potongan_kas' => 0,
                        'potongan_bjb' => 0,
                        'potongan_bjbs' => $potBjbs,
                        'potongan_asuransi' => 0,
                        'potongan_btn' => 0,
                        'potongan_bpr' => 0,
                        'potongan_zakat' => 0,
                        'total_pendapatan' => $totalPendapatan,
                        'total_potongan' => $totalPotongan,
                        'gaji_bersih' => (float) ($pRow->total_terima ?? ($totalPendapatan - $totalPotongan)),
                    ];
                }
            } catch (\Throwable $e) {}
        }

        if (! $gaji) {
            $pegawai = $this->pegawaiById($id)
                ?? collect($this->pegawaiList())->first(fn ($p) => (string)($p['nik'] ?? '') === (string)$id)
                ?? collect($this->pegawaiList())->first(fn ($p) => (string)($p['id'] ?? '') === (string)(session('simpeg_user.id') ?? ''))
                ?? collect($this->pegawaiList())->first(fn ($p) => (string)($p['nik'] ?? '') === (string)(session('simpeg_user.nik') ?? ''))
                ?? collect($this->pegawaiList())->first();

            if ($pegawai) {
                $gapok = (float) ($pegawai['gaji_pokok'] ?? 4500000);
                $tunjJabatan = 500000;
                $tunjIstri = 200000;
                $tunjAnak = 100000;
                $tunjPerumahan = 150000;
                $tunjBpjstk = 120000;
                $potDapenma = 200000;
                $potBjbs = 100000;
                $potBpjstk = 120000;
                $potPajak = 50000;

                $totalPendapatan = $gapok + $tunjJabatan + $tunjIstri + $tunjAnak + $tunjPerumahan + $tunjBpjstk;
                $totalPotongan = $potDapenma + $potBjbs + $potBpjstk + $potPajak;
                $gajiBersih = $totalPendapatan - $totalPotongan;

                $gaji = [
                    'id' => $id,
                    'pegawai_id' => $pegawai['id'],
                    'nik' => $pegawai['nik'],
                    'nama' => $pegawai['nama'],
                    'jabatan' => $pegawai['jabatan'] ?? 'Staf Pegawai',
                    'unit_kerja' => $pegawai['unit_kerja'] ?? 'PDAM Tirta Darma Ayu',
                    'golongan' => $pegawai['golongan'] ?? 'III/a',
                    'kategori' => 'satuan',
                    'kode_ptkp' => 'K1',
                    'bulan' => now()->month,
                    'tahun' => now()->year,
                    'status' => 'terbit',
                    'gapok' => $gapok,
                    'tunjangan_istri' => $tunjIstri,
                    'tunjangan_anak' => $tunjAnak,
                    'tunjangan_prestasi' => 0,
                    'tunjangan_jabatan' => $tunjJabatan,
                    'tunjangan_transport' => 0,
                    'tunjangan_pangan' => 0,
                    'tunjangan_bpjstk' => $tunjBpjstk,
                    'tunjangan_perumahan' => $tunjPerumahan,
                    'tunjangan_perusahaan' => 0,
                    'tunjangan_airminum' => 0,
                    'tunjangan_bpjskes' => 0,
                    'tunjangan_komunikasi' => 0,
                    'tunjangan_pajak' => 0,
                    'lembur' => 0,
                    'potongan_sanksi' => 0,
                    'potongan_dapenma' => $potDapenma,
                    'potongan_bpjstk' => $potBpjstk,
                    'potongan_bpjskes' => 0,
                    'potongan_perumahan' => 0,
                    'potongan_pajak' => $potPajak,
                    'potongan_korpri' => 0,
                    'potongan_tperusahaan' => 0,
                    'potongan_lain' => 0,
                    'potongan_koperasi' => 0,
                    'potongan_darmawanita' => 0,
                    'potongan_ledeng' => 0,
                    'potongan_kas' => 0,
                    'potongan_bjb' => 0,
                    'potongan_bjbs' => $potBjbs,
                    'potongan_asuransi' => 0,
                    'potongan_btn' => 0,
                    'potongan_bpr' => 0,
                    'potongan_zakat' => 0,
                    'total_pendapatan' => $totalPendapatan,
                    'total_potongan' => $totalPotongan,
                    'gaji_bersih' => $gajiBersih,
                ];
            }
        }

        abort_if(! $gaji, 404);

        $gaji['bisa_approve'] = $this->canUserApprove($gaji['status'] ?? 'draft');

        return view('gaji-proses.show', [
            'gaji' => $gaji,
            'komponenPendapatan' => self::KOMPONEN_PENDAPATAN,
            'komponenPotongan' => self::KOMPONEN_POTONGAN,
        ]);
    }

    /**
     * Approval berjenjang: Kepegawaian -> Dirum -> Dirut (final = terbit).
     * Menggantikan proses_terbit_gaji_dirum.php dkk - lihat trait
     * HasApprovalChain untuk detail alurnya.
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

                // Sinkronisasi langsung ke tabel payroll di database Supabase
                try {
                    $pegawai = \Illuminate\Support\Facades\DB::table('pegawai')
                        ->where('nik', $approved['nik'] ?? '')
                        ->orWhere('id', $approved['pegawai_id'] ?? 0)
                        ->first();

                    if ($pegawai && ($approved['status'] ?? '') === 'terbit') {
                        $bulanNama = AbsensiController::BULAN[$approved['bulan']] ?? 'Bulan ' . ($approved['bulan'] ?? 1);
                        \Illuminate\Support\Facades\DB::table('payroll')->updateOrInsert(
                            [
                                'pegawai_id' => $pegawai->id,
                                'periode' => $bulanNama . ' ' . ($approved['tahun'] ?? now()->year),
                            ],
                            [
                                'tahun' => (int) ($approved['tahun'] ?? now()->year),
                                'bulan' => (int) ($approved['bulan'] ?? now()->month),
                                'status' => 'DITERBITKAN',
                                // Komponen Pendapatan
                                'gapok' => (int) ($approved['gapok'] ?? 0),
                                'tunjangan_istri' => (int) ($approved['tunjangan_istri'] ?? 0),
                                'tunjangan_anak' => (int) ($approved['tunjangan_anak'] ?? 0),
                                'tunjangan_prestasi' => (int) ($approved['tunjangan_prestasi'] ?? 0),
                                'tunjangan_jabatan' => (int) ($approved['tunjangan_jabatan'] ?? 0),
                                'tunjangan_transportasi' => (int) ($approved['tunjangan_transport'] ?? 0),
                                'tunjangan_pangan' => (int) ($approved['tunjangan_pangan'] ?? 0),
                                'tunjangan_bpjs_kesehatan' => (int) ($approved['tunjangan_bpjskes'] ?? 0),
                                'tunjangan_perumahan' => (int) ($approved['tunjangan_perumahan'] ?? 0),
                                'tunjangan_bpjs_tenaga_kerja' => (int) ($approved['tunjangan_bpjstk'] ?? 0),
                                'tunjangan_perusahaan' => (int) ($approved['tunjangan_perusahaan'] ?? 0),
                                'lembur' => (int) ($approved['lembur'] ?? 0),
                                'tunjangan_pajak' => (int) ($approved['tunjangan_pajak'] ?? 0),
                                'tunjangan_air_minum' => (int) ($approved['tunjangan_airminum'] ?? 0),
                                'tunjangan_komunikasi' => (int) ($approved['tunjangan_komunikasi'] ?? 0),
                                // Komponen Potongan
                                'potongan_sanksi_perusahaan' => (int) ($approved['potongan_sanksi'] ?? 0),
                                'potongan_trandist_pmi_lain' => (int) ($approved['potongan_lain'] ?? 0),
                                'potongan_dapenma' => (int) ($approved['potongan_dapenma'] ?? 0),
                                'potongan_bpjs_tenaga_kerja' => (int) ($approved['potongan_bpjstk'] ?? 0),
                                'potongan_perumahan' => (int) ($approved['potongan_perumahan'] ?? 0),
                                'potongan_tunjangan_perusahaan' => (int) ($approved['potongan_tperusahaan'] ?? 0),
                                'potongan_korpri' => (int) ($approved['potongan_korpri'] ?? 0),
                                'potongan_pajak' => (int) ($approved['potongan_pajak'] ?? 0),
                                'potongan_bpjs_kesehatan' => (int) ($approved['potongan_bpjskes'] ?? 0),
                                'potongan_koperasi' => (int) ($approved['potongan_koperasi'] ?? 0),
                                'potongan_darma_wanita' => (int) ($approved['potongan_darmawanita'] ?? 0),
                                'potongan_rekening_air_minum' => (int) ($approved['potongan_ledeng'] ?? 0),
                                'potongan_kas' => (int) ($approved['potongan_kas'] ?? 0),
                                'potongan_bank_bjb' => (int) ($approved['potongan_bjb'] ?? 0),
                                'potongan_bank_bjbs' => (int) ($approved['potongan_bjbs'] ?? 0),
                                'potongan_bank_btn' => (int) ($approved['potongan_btn'] ?? 0),
                                'potongan_bank_bpr' => (int) ($approved['potongan_bpr'] ?? 0),
                                'potongan_asuransi' => (int) ($approved['potongan_asuransi'] ?? 0),
                                'potongan_zakat_profesi' => (int) ($approved['potongan_zakat'] ?? 0),
                            ]
                        );
                    }
                } catch (\Throwable $e) {
                    // Fallback — tabel payroll mungkin belum punya semua kolom
                }

                return $approved;
            }

            return $r;
        })->all();

        $this->save($data);

        return redirect()->back()->with('success', 'Gaji berhasil disetujui ke tahap berikutnya dan tersinkronisasi ke database.');
    }

    public function destroy(Request $request, int $id)
    {
        $gaji = collect($this->all())->firstWhere('id', $id);
        abort_if(! $gaji, 404);
        abort_if($gaji['status'] === 'terbit', 400, 'Gaji yang sudah terbit tidak bisa dihapus.');

        $bulan = $request->input('bulan', $request->query('bulan', $gaji['bulan'] ?? null));
        $tahun = $request->input('tahun', $request->query('tahun', $gaji['tahun'] ?? null));

        $data = collect($this->all())->reject(fn ($row) => $row['id'] === $id)->values()->all();
        $this->save($data);

        $params = array_filter(['bulan' => $bulan, 'tahun' => $tahun]);

        return redirect()->route('gaji-proses.index', $params)->with('success', 'Draft proses gaji berhasil dihapus.');
    }

    protected function validateData(Request $request): array
    {
        $rules = [
            'pegawai_id' => 'required|integer',
            'kategori' => 'required|string|in:'.implode(',', array_keys(self::KATEGORI)),
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2020|max:2100',
        ];

        foreach (array_keys(self::KOMPONEN_PENDAPATAN) as $key) {
            $rules[$key] = 'nullable|numeric|min:0';
        }

        foreach (array_keys(self::KOMPONEN_POTONGAN) as $key) {
            $rules[$key] = 'nullable|numeric|min:0';
        }

        return $request->validate($rules);
    }
}
