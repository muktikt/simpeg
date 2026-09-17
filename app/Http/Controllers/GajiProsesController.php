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

    protected function getLocalData(): array
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

    public function all(): array
    {
        try {
            $rows = \Illuminate\Support\Facades\DB::table('payroll')
                ->leftJoin('pegawai', 'payroll.pegawai_id', '=', 'pegawai.id')
                ->select(
                    'payroll.*',
                    'pegawai.nik as p_nik',
                    'pegawai.name as p_name',
                    'pegawai.jabatan as p_jabatan',
                    'pegawai.unit_kerja as p_unit_kerja',
                    'pegawai.golongan as p_golongan'
                )
                ->orderBy('payroll.id', 'desc')
                ->get();

            return $rows->map(fn ($r) => $this->mapPayrollRowToGajiArray($r))->all();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('DB payroll read failed, fallback to local: ' . $e->getMessage());
        }

        return [];
    }

    public function save(array $data): void
    {
        $file = $this->storageFile();
        $clean = array_values($data);
        @file_put_contents($file, json_encode($clean, JSON_PRETTY_PRINT));
        session()->put('dummy_gaji_proses', $clean);
    }

    protected function mapPayrollRowToGajiArray(object $r): array
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
        $totalPotongan = (float) ($r->total_potongan ?? 0);
        $gajiBersih = (float) ($r->gaji_bersih ?? 0);

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

        if ($totalPotongan <= 0) {
            $totalPotongan = (float) ($r->potongan_sanksi_perusahaan ?? 0)
                + (float) ($r->potongan_dapenma ?? 0)
                + (float) ($r->potongan_bpjs_tenaga_kerja ?? 0)
                + (float) ($r->potongan_bpjs_kesehatan ?? 0)
                + (float) ($r->potongan_perumahan ?? 0)
                + (float) ($r->potongan_pajak ?? 0)
                + (float) ($r->potongan_korpri ?? 0)
                + (float) ($r->potongan_tunjangan_perusahaan ?? 0)
                + (float) ($r->potongan_trandist_pmi_lain ?? 0)
                + (float) ($r->potongan_koperasi ?? 0)
                + (float) ($r->potongan_darma_wanita ?? 0)
                + (float) ($r->potongan_rekening_air_minum ?? 0)
                + (float) ($r->potongan_kas ?? 0)
                + (float) ($r->potongan_bank_bjb ?? 0)
                + (float) ($r->potongan_bank_bjbs ?? 0)
                + (float) ($r->potongan_asuransi ?? 0)
                + (float) ($r->potongan_bank_btn ?? 0)
                + (float) ($r->potongan_bank_bpr ?? 0)
                + (float) ($r->potongan_zakat_profesi ?? 0);
        }

        if ($gajiBersih <= 0) {
            $gajiBersih = $totalPendapatan - $totalPotongan;
        }

        return [
            'id' => $r->id,
            'pegawai_id' => $r->pegawai_id,
            'nik' => $nik,
            'nama' => $nama,
            'jabatan' => (string) ($r->p_jabatan ?? $r->jabatan ?? 'Pegawai'),
            'unit_kerja' => (string) ($r->p_unit_kerja ?? $r->unit_kerja ?? 'Kantor Pusat'),
            'golongan' => (string) ($r->p_golongan ?? $r->golongan ?? 'III/a'),
            'kategori' => (string) ($r->kategori ?? 'satuan'),
            'kode_ptkp' => (string) ($r->kode_ptkp ?? 'K1'),
            'bulan' => (int) ($r->bulan ?? now()->month),
            'tahun' => (int) ($r->tahun ?? now()->year),
            'status' => $status,
            'disetujui_oleh' => (string) ($r->disetujui_oleh ?? ''),

            // Komponen Pendapatan
            'gapok' => (float) ($r->gapok ?? 0),
            'tunjangan_istri' => (float) ($r->tunjangan_istri ?? 0),
            'tunjangan_anak' => (float) ($r->tunjangan_anak ?? 0),
            'tunjangan_prestasi' => (float) ($r->tunjangan_prestasi ?? 0),
            'tunjangan_jabatan' => (float) ($r->tunjangan_jabatan ?? 0),
            'tunjangan_transport' => (float) ($r->tunjangan_transportasi ?? $r->tunjangan_transport ?? 0),
            'tunjangan_pangan' => (float) ($r->tunjangan_pangan ?? 0),
            'tunjangan_bpjstk' => (float) ($r->tunjangan_bpjs_tenaga_kerja ?? $r->tunjangan_bpjstk ?? 0),
            'tunjangan_perumahan' => (float) ($r->tunjangan_perumahan ?? 0),
            'tunjangan_perusahaan' => (float) ($r->tunjangan_perusahaan ?? 0),
            'tunjangan_airminum' => (float) ($r->tunjangan_air_minum ?? $r->tunjangan_airminum ?? 0),
            'tunjangan_bpjskes' => (float) ($r->tunjangan_bpjs_kesehatan ?? $r->tunjangan_bpjskes ?? 0),
            'tunjangan_komunikasi' => (float) ($r->tunjangan_komunikasi ?? 0),
            'tunjangan_pajak' => (float) ($r->tunjangan_pajak ?? 0),
            'lembur' => (float) ($r->lembur ?? 0),

            // Komponen Potongan
            'potongan_sanksi' => (float) ($r->potongan_sanksi_perusahaan ?? $r->potongan_sanksi ?? 0),
            'potongan_dapenma' => (float) ($r->potongan_dapenma ?? 0),
            'potongan_bpjstk' => (float) ($r->potongan_bpjs_tenaga_kerja ?? $r->potongan_bpjstk ?? 0),
            'potongan_bpjskes' => (float) ($r->potongan_bpjs_kesehatan ?? $r->potongan_bpjskes ?? 0),
            'potongan_perumahan' => (float) ($r->potongan_perumahan ?? 0),
            'potongan_pajak' => (float) ($r->potongan_pajak ?? 0),
            'potongan_korpri' => (float) ($r->potongan_korpri ?? 0),
            'potongan_tperusahaan' => (float) ($r->potongan_tunjangan_perusahaan ?? $r->potongan_tperusahaan ?? 0),
            'potongan_lain' => (float) ($r->potongan_trandist_pmi_lain ?? $r->potongan_lain ?? 0),
            'potongan_koperasi' => (float) ($r->potongan_koperasi ?? 0),
            'potongan_darmawanita' => (float) ($r->potongan_darma_wanita ?? $r->potongan_darmawanita ?? 0),
            'potongan_ledeng' => (float) ($r->potongan_rekening_air_minum ?? $r->potongan_ledeng ?? 0),
            'potongan_kas' => (float) ($r->potongan_kas ?? 0),
            'potongan_bjb' => (float) ($r->potongan_bank_bjb ?? $r->potongan_bjb ?? 0),
            'potongan_bjbs' => (float) ($r->potongan_bank_bjbs ?? $r->potongan_bjbs ?? 0),
            'potongan_asuransi' => (float) ($r->potongan_asuransi ?? 0),
            'potongan_btn' => (float) ($r->potongan_bank_btn ?? $r->potongan_btn ?? 0),
            'potongan_bpr' => (float) ($r->potongan_bank_bpr ?? $r->potongan_bpr ?? 0),
            'potongan_zakat' => (float) ($r->potongan_zakat_profesi ?? $r->potongan_zakat ?? 0),

            'total_pendapatan' => $totalPendapatan,
            'total_potongan' => $totalPotongan,
            'gaji_bersih' => $gajiBersih,
        ];
    }

    public function findItem(mixed $id): ?array
    {
        try {
            $row = \Illuminate\Support\Facades\DB::table('payroll')
                ->leftJoin('pegawai', 'payroll.pegawai_id', '=', 'pegawai.id')
                ->select(
                    'payroll.*',
                    'pegawai.nik as p_nik',
                    'pegawai.name as p_name',
                    'pegawai.jabatan as p_jabatan',
                    'pegawai.unit_kerja as p_unit_kerja',
                    'pegawai.golongan as p_golongan'
                )
                ->where('payroll.id', $id)
                ->first();

            if ($row) {
                return $this->mapPayrollRowToGajiArray($row);
            }
        } catch (\Throwable $e) {}

        return null;
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

        $potonganKeu = null;
        if (! empty($pegawai['nik'])) {
            try {
                $potonganKeu = \Illuminate\Support\Facades\DB::table('potongan_keu')
                    ->where('tipe', 'gaji')
                    ->where('nik', $pegawai['nik'])
                    ->orderByDesc('id')
                    ->first();
            } catch (\Throwable $e) {}
        }

        return [
            'kawin' => $kawin,
            'jml_istri' => $jmlIstri,
            'jml_anak' => $jmlAnak,
            'jml_anak_pajak' => $jmlAnakPajak,
            'kode_ptkp' => $kodePtkp,
            'potongan_keu' => $potonganKeu,
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

        // Cari UUID pegawai di DB Supabase
        $dbPegawaiId = $pegawai['db_id'] ?? null;
        if (! $dbPegawaiId && ! empty($validated['nik'])) {
            $dbPegawaiId = \Illuminate\Support\Facades\DB::table('pegawai')->where('nik', $validated['nik'])->value('id');
        }

        $bulanNama = AbsensiController::BULAN[$validated['bulan']] ?? 'Bulan ' . $validated['bulan'];
        $periode = $bulanNama . ' ' . $validated['tahun'];

        $insertedToDb = false;
        if ($dbPegawaiId) {
            try {
                $newId = \Illuminate\Support\Facades\DB::table('payroll')->insertGetId([
                    'pegawai_id' => $dbPegawaiId,
                    'periode' => $periode,
                    'tahun' => (int) $validated['tahun'],
                    'bulan' => (int) $validated['bulan'],
                    'status' => 'draft',
                    'kategori' => $validated['kategori'],
                    'kode_ptkp' => $validated['kode_ptkp'],
                    'total_pendapatan' => (int) $totalPendapatan,
                    'total_potongan' => (int) $totalPotongan,
                    'gaji_bersih' => (int) ($totalPendapatan - $totalPotongan),

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

                    // Komponen Potongan
                    'potongan_sanksi_perusahaan' => (int) ($validated['potongan_sanksi'] ?? 0),
                    'potongan_dapenma' => (int) ($validated['potongan_dapenma'] ?? 0),
                    'potongan_bpjs_tenaga_kerja' => (int) ($validated['potongan_bpjstk'] ?? 0),
                    'potongan_bpjs_kesehatan' => (int) ($validated['potongan_bpjskes'] ?? 0),
                    'potongan_perumahan' => (int) ($validated['potongan_perumahan'] ?? 0),
                    'potongan_pajak' => (int) ($validated['potongan_pajak'] ?? 0),
                    'potongan_korpri' => (int) ($validated['potongan_korpri'] ?? 0),
                    'potongan_tunjangan_perusahaan' => (int) ($validated['potongan_tperusahaan'] ?? 0),
                    'potongan_trandist_pmi_lain' => (int) ($validated['potongan_lain'] ?? 0),
                    'potongan_koperasi' => (int) ($validated['potongan_koperasi'] ?? 0),
                    'potongan_darma_wanita' => (int) ($validated['potongan_darmawanita'] ?? 0),
                    'potongan_rekening_air_minum' => (int) ($validated['potongan_ledeng'] ?? 0),
                    'potongan_kas' => (int) ($validated['potongan_kas'] ?? 0),
                    'potongan_bank_bjb' => (int) ($validated['potongan_bjb'] ?? 0),
                    'potongan_bank_bjbs' => (int) ($validated['potongan_bjbs'] ?? 0),
                    'potongan_asuransi' => (int) ($validated['potongan_asuransi'] ?? 0),
                    'potongan_bank_btn' => (int) ($validated['potongan_btn'] ?? 0),
                    'potongan_bank_bpr' => (int) ($validated['potongan_bpr'] ?? 0),
                    'potongan_zakat_profesi' => (int) ($validated['potongan_zakat'] ?? 0),
                ]);

                $insertedToDb = true;
                $validated['id'] = $newId;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('DB payroll insert failed: ' . $e->getMessage());
            }
        }

        // Simpan juga ke file lokal sebagai fallback
        $localData = $this->getLocalData();
        if (! $insertedToDb) {
            $newId = $localData ? max(array_column($localData, 'id')) + 1 : 1;
            $validated['id'] = $newId;
        }
        $localData[] = $validated;
        $this->save($localData);

        return redirect()->route('gaji-proses.index', ['bulan' => $validated['bulan'], 'tahun' => $validated['tahun']])
            ->with('success', 'Proses gaji untuk '.$validated['nama'].' berhasil disimpan dan masuk ke antrean approval.');
    }

    public function show(mixed $id)
    {
        $gaji = $this->findItem($id);
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
        $row = $this->findItem($id);
        abort_if(! $row, 404);
        abort_unless($this->canUserApprove($row['status']), 403, 'Kamu tidak berhak menyetujui tahap ini.');

        $stage = $this->nextStageFor($row['status']);
        $nextStatus = ($stage === 'dirut') ? 'terbit' : $stage;
        $approverNama = session('simpeg_user.nama_peg', 'Admin');

        // Update langsung di tabel payroll di database Supabase
        try {
            $dbStatus = ($nextStatus === 'terbit') ? 'DITERBITKAN' : $nextStatus;
            \Illuminate\Support\Facades\DB::table('payroll')
                ->where('id', $id)
                ->update([
                    'status' => $dbStatus,
                    'disetujui_oleh' => $approverNama,
                    'updated_at' => now(),
                ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('DB payroll update status failed: ' . $e->getMessage());
        }

        // Update juga di file lokal
        $localData = $this->getLocalData();
        if (! empty($localData)) {
            $updated = collect($localData)->map(function ($r) use ($id, $nextStatus, $approverNama) {
                if ((int) ($r['id'] ?? 0) === $id) {
                    $r['status'] = $nextStatus;
                    $r['disetujui_oleh'] = $approverNama;
                    if ($nextStatus === 'terbit') {
                        $r['tgl_terbit'] = now()->toDateString();
                    }
                }
                return $r;
            })->all();
            $this->save($updated);
        }

        $label = self::approvalStatusLabel($nextStatus);
        return redirect()->back()->with('success', "Gaji berhasil disetujui ({$label}) dan tersimpan di database.");
    }

    public function destroy(Request $request, int $id)
    {
        $row = $this->findItem($id);
        abort_if(! $row, 404);
        abort_if($row['status'] === 'terbit', 400, 'Gaji yang sudah terbit tidak bisa dihapus.');

        $bulan = $request->input('bulan', $request->query('bulan', $row['bulan'] ?? null));
        $tahun = $request->input('tahun', $request->query('tahun', $row['tahun'] ?? null));

        // Hapus dari tabel payroll Supabase
        try {
            \Illuminate\Support\Facades\DB::table('payroll')->where('id', $id)->delete();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('DB payroll delete failed: ' . $e->getMessage());
        }

        // Hapus dari file lokal
        $localData = $this->getLocalData();
        if (! empty($localData)) {
            $filtered = collect($localData)->reject(fn ($r) => (int)($r['id'] ?? 0) === $id)->values()->all();
            $this->save($filtered);
        }

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
